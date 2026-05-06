import { promises as fs } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const appRoot = path.resolve(scriptDirectory, '..');
const checkOnly = process.argv.includes('--check');
const excludedDirectoryNames = new Set([
    '.git',
    'node_modules',
    'storage',
    'vendor',
]);
const excludedRelativeDirectories = ['bootstrap/cache', 'public/build'];
const supportedExtensions = new Set(['.css', '.js', '.php', '.vue']);

const changedFiles = [];

await walk(appRoot);

if (changedFiles.length === 0) {
    console.log('Nenhum comentario removivel encontrado.');
} else if (checkOnly) {
    console.error('Comentarios encontrados nos arquivos:');
    for (const file of changedFiles) {
        console.error(`- ${file}`);
    }
    process.exitCode = 1;
} else {
    console.log('Comentarios removidos dos arquivos:');
    for (const file of changedFiles) {
        console.log(`- ${file}`);
    }
}

async function walk(directory) {
    const entries = await fs.readdir(directory, { withFileTypes: true });

    for (const entry of entries) {
        const absolutePath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            if (!shouldSkipDirectory(absolutePath)) {
                await walk(absolutePath);
            }
            continue;
        }

        if (!entry.isFile() || !isSupportedFile(absolutePath)) {
            continue;
        }

        const original = await fs.readFile(absolutePath, 'utf8');
        const stripped = stripBlankLineWhitespace(
            stripFileComments(absolutePath, original),
        );

        if (stripped === original) {
            continue;
        }

        const relativePath = normalizePath(
            path.relative(appRoot, absolutePath),
        );
        changedFiles.push(relativePath);

        if (!checkOnly) {
            await fs.writeFile(absolutePath, stripped);
        }
    }
}

function shouldSkipDirectory(directory) {
    if (excludedDirectoryNames.has(path.basename(directory))) {
        return true;
    }

    const relativePath = normalizePath(path.relative(appRoot, directory));
    return excludedRelativeDirectories.some(
        (excludedPath) =>
            relativePath === excludedPath ||
            relativePath.startsWith(`${excludedPath}/`),
    );
}

function isSupportedFile(filePath) {
    if (filePath.endsWith('.blade.php')) {
        return true;
    }

    return supportedExtensions.has(path.extname(filePath).toLowerCase());
}

function stripFileComments(filePath, source) {
    const extension = path.extname(filePath).toLowerCase();

    if (filePath.endsWith('.blade.php')) {
        return stripBladeComments(
            stripHtmlComments(stripPhpComments(source, false)),
        );
    }

    if (extension === '.php') {
        return stripPhpComments(source, true);
    }

    if (extension === '.js') {
        return stripJavaScriptComments(source);
    }

    if (extension === '.vue') {
        return stripVueComments(source);
    }

    if (extension === '.css') {
        return stripCssComments(source);
    }

    return source;
}

function stripVueComments(source) {
    const blockPattern = /<(script|style)\b[^>]*>[\s\S]*?<\/\s*\1\s*>/gi;
    let output = '';
    let lastIndex = 0;

    for (const match of source.matchAll(blockPattern)) {
        const block = match[0];
        const tagName = match[1].toLowerCase();
        const startIndex = match.index ?? 0;

        output += stripBladeComments(
            stripHtmlComments(source.slice(lastIndex, startIndex)),
        );
        output += stripVueBlock(block, tagName);
        lastIndex = startIndex + block.length;
    }

    output += stripBladeComments(stripHtmlComments(source.slice(lastIndex)));

    return output;
}

function stripVueBlock(block, tagName) {
    const openingEnd = block.indexOf('>') + 1;
    const closingMatch = block.match(
        new RegExp(`<\\/\\s*${tagName}\\s*>\\s*$`, 'i'),
    );

    if (!openingEnd || !closingMatch || closingMatch.index === undefined) {
        return block;
    }

    const opening = block.slice(0, openingEnd);
    const content = block.slice(openingEnd, closingMatch.index);
    const closing = block.slice(closingMatch.index);
    const strippedContent =
        tagName === 'script'
            ? stripJavaScriptComments(content)
            : stripCssComments(content);

    return `${opening}${strippedContent}${closing}`;
}

function stripBladeComments(source) {
    return source.replace(/\{\{--[\s\S]*?--\}\}/g, replacementForRemovedBlock);
}

function stripHtmlComments(source) {
    return source.replace(/<!--[\s\S]*?-->/g, replacementForRemovedBlock);
}

function stripCssComments(source) {
    let output = '';
    let index = 0;

    while (index < source.length) {
        if (source.startsWith('/*', index)) {
            const endIndex = source.indexOf('*/', index + 2);
            const commentEnd = endIndex === -1 ? source.length : endIndex + 2;
            output += replacementForRemovedBlock(
                source.slice(index, commentEnd),
            );
            index = commentEnd;
            continue;
        }

        const char = source[index];

        if (char === '"' || char === "'") {
            const quoted = readQuoted(source, index, char);
            output += quoted.text;
            index = quoted.index;
            continue;
        }

        output += char;
        index += 1;
    }

    return output;
}

function stripPhpComments(source, stripHashComments) {
    let output = '';
    let index = 0;

    if (source.startsWith('#!')) {
        const lineEnd = findLineBreakStart(source, 0);
        const endIndex = lineEnd === -1 ? source.length : lineEnd;
        output += source.slice(0, endIndex);
        index = endIndex;
    }

    while (index < source.length) {
        if (source.startsWith('//', index)) {
            index = skipLineComment(source, index);
            continue;
        }

        if (stripHashComments && source[index] === '#') {
            index = skipLineComment(source, index);
            continue;
        }

        if (source.startsWith('/*', index)) {
            const endIndex = source.indexOf('*/', index + 2);
            const commentEnd = endIndex === -1 ? source.length : endIndex + 2;
            output += replacementForRemovedBlock(
                source.slice(index, commentEnd),
            );
            index = commentEnd;
            continue;
        }

        if (source.startsWith('<<<', index)) {
            const heredoc = readPhpHeredoc(source, index);

            if (heredoc) {
                output += heredoc.text;
                index = heredoc.index;
                continue;
            }
        }

        const char = source[index];

        if (char === '"' || char === "'" || char === '`') {
            const quoted = readQuoted(source, index, char);
            output += quoted.text;
            index = quoted.index;
            continue;
        }

        output += char;
        index += 1;
    }

    return output;
}

function stripJavaScriptComments(source) {
    let prefix = '';
    let startIndex = 0;

    if (source.startsWith('#!')) {
        const lineEnd = findLineBreakStart(source, 0);
        startIndex = lineEnd === -1 ? source.length : lineEnd;
        prefix = source.slice(0, startIndex);
    }

    return prefix + stripJavaScriptRange(source, startIndex, false).text;
}

function stripJavaScriptRange(source, startIndex, stopAtBrace) {
    let output = '';
    let index = startIndex;
    let braceDepth = 0;
    let bracketDepth = 0;
    let parenDepth = 0;

    while (index < source.length) {
        const char = source[index];

        if (
            stopAtBrace &&
            char === '}' &&
            braceDepth === 0 &&
            bracketDepth === 0 &&
            parenDepth === 0
        ) {
            return { text: output, index };
        }

        if (source.startsWith('//', index)) {
            index = skipLineComment(source, index);
            continue;
        }

        if (source.startsWith('/*', index)) {
            const endIndex = source.indexOf('*/', index + 2);
            const commentEnd = endIndex === -1 ? source.length : endIndex + 2;
            output += replacementForRemovedBlock(
                source.slice(index, commentEnd),
            );
            index = commentEnd;
            continue;
        }

        if (char === '"' || char === "'") {
            const quoted = readQuoted(source, index, char);
            output += quoted.text;
            index = quoted.index;
            continue;
        }

        if (char === '`') {
            const template = readJavaScriptTemplate(source, index);
            output += template.text;
            index = template.index;
            continue;
        }

        if (char === '/' && shouldStartRegexLiteral(output)) {
            const regex = readJavaScriptRegex(source, index);
            output += regex.text;
            index = regex.index;
            continue;
        }

        if (char === '{') {
            braceDepth += 1;
        } else if (char === '}' && braceDepth > 0) {
            braceDepth -= 1;
        } else if (char === '[') {
            bracketDepth += 1;
        } else if (char === ']' && bracketDepth > 0) {
            bracketDepth -= 1;
        } else if (char === '(') {
            parenDepth += 1;
        } else if (char === ')' && parenDepth > 0) {
            parenDepth -= 1;
        }

        output += char;
        index += 1;
    }

    return { text: output, index };
}

function readJavaScriptTemplate(source, startIndex) {
    let output = '`';
    let index = startIndex + 1;

    while (index < source.length) {
        if (source[index] === '\\') {
            output += source.slice(index, index + 2);
            index += 2;
            continue;
        }

        if (source.startsWith('${', index)) {
            const expression = stripJavaScriptRange(source, index + 2, true);
            output += '${' + expression.text;
            index = expression.index;

            if (source[index] === '}') {
                output += '}';
                index += 1;
            }

            continue;
        }

        output += source[index];

        if (source[index] === '`') {
            index += 1;
            break;
        }

        index += 1;
    }

    return { text: output, index };
}

function readJavaScriptRegex(source, startIndex) {
    let output = '/';
    let index = startIndex + 1;
    let inCharacterClass = false;

    while (index < source.length) {
        const char = source[index];

        if (char === '\\') {
            output += source.slice(index, index + 2);
            index += 2;
            continue;
        }

        if (char === '[') {
            inCharacterClass = true;
        } else if (char === ']') {
            inCharacterClass = false;
        } else if (char === '/' && !inCharacterClass) {
            output += char;
            index += 1;

            while (/[a-z]/i.test(source[index] ?? '')) {
                output += source[index];
                index += 1;
            }

            return { text: output, index };
        }

        output += char;
        index += 1;
    }

    return { text: output, index };
}

function shouldStartRegexLiteral(output) {
    const trimmedOutput = output.trimEnd();

    if (trimmedOutput === '') {
        return true;
    }

    const lastChar = trimmedOutput.at(-1) ?? '';

    if ('([{=,:;!?&|+-*~^<>%'.includes(lastChar)) {
        return true;
    }

    const wordMatch = trimmedOutput.match(/([A-Za-z_$][\w$]*)$/);
    const previousWord = wordMatch?.[1];

    return [
        'await',
        'case',
        'delete',
        'else',
        'in',
        'new',
        'of',
        'return',
        'throw',
        'typeof',
        'void',
        'yield',
    ].includes(previousWord ?? '');
}

function readQuoted(source, startIndex, quote) {
    let output = quote;
    let index = startIndex + 1;

    while (index < source.length) {
        if (source[index] === '\\') {
            output += source.slice(index, index + 2);
            index += 2;
            continue;
        }

        output += source[index];

        if (source[index] === quote) {
            index += 1;
            break;
        }

        index += 1;
    }

    return { text: output, index };
}

function readPhpHeredoc(source, startIndex) {
    const firstLineEnd = findLineBreakStart(source, startIndex);

    if (firstLineEnd === -1) {
        return null;
    }

    const opener = source.slice(startIndex, firstLineEnd);
    const labelMatch = opener.match(
        /^<<<\s*(?:'([^']+)'|"([^"]+)"|([A-Za-z_][A-Za-z0-9_]*))/,
    );
    const label = labelMatch?.[1] ?? labelMatch?.[2] ?? labelMatch?.[3];

    if (!label) {
        return null;
    }

    let index = firstLineEnd;

    while (index < source.length) {
        const lineEnd = findLineBreakStart(source, index + 1);
        const endIndex = lineEnd === -1 ? source.length : lineEnd;
        const lineStart = findPreviousLineBreakEnd(source, index);
        const line = source.slice(lineStart, endIndex).trim();

        if (line === label || line === `${label};`) {
            const finalIndex = lineEnd === -1 ? source.length : lineEnd;
            return {
                text: source.slice(startIndex, finalIndex),
                index: finalIndex,
            };
        }

        index = lineEnd === -1 ? source.length : lineEnd + 1;
    }

    return null;
}

function skipLineComment(source, startIndex) {
    const lineEnd = findLineBreakStart(source, startIndex);
    return lineEnd === -1 ? source.length : lineEnd;
}

function replacementForRemovedBlock(block) {
    const lineBreaks = block.match(/\r\n|\r|\n/g);
    return lineBreaks ? lineBreaks.join('') : ' ';
}

function findLineBreakStart(source, startIndex) {
    const lineFeed = source.indexOf('\n', startIndex);
    const carriageReturn = source.indexOf('\r', startIndex);

    if (lineFeed === -1) {
        return carriageReturn;
    }

    if (carriageReturn === -1) {
        return lineFeed;
    }

    return Math.min(lineFeed, carriageReturn);
}

function findPreviousLineBreakEnd(source, startIndex) {
    const previousLineFeed = source.lastIndexOf('\n', startIndex - 1);
    const previousCarriageReturn = source.lastIndexOf('\r', startIndex - 1);
    const previousBreak = Math.max(previousLineFeed, previousCarriageReturn);
    return previousBreak === -1 ? 0 : previousBreak + 1;
}

function normalizePath(filePath) {
    return filePath.split(path.sep).join('/');
}

function stripBlankLineWhitespace(source) {
    return source.replace(/^[ \t]+$/gm, '');
}
