<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompositionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $compositions = [
            [
                'id' => 1,
                'name' => 'Yunara (Ionia)',
                'notes' => 'Peças: (3) Ionias, sendo uma peça 2*
Itens: Guinsoo ou IE
Estratégia: Fast 9, roletando no 8 para bater as 2* principais

Idealmente, jogue com Ionia de nível, ouro ou experiência. Você pode optar por uma composição com 3 Ionias + uma boa linha de frente. Fiddlesticks e Taric são opções flexíveis. Priorize os itens de Yunara > Tanque > Sett. Yunara só precisa de Guinsoo + IE, o terceiro item é flexível: Titan\'s > Striker Flail > Kraken

Stage 2: O ideal é que você tenha Ionia que concede ouro ou EXP. Use itens de impacto e jogue para conseguir uma sequência de vitórias. Jogue em torno de Yordles ou Defensores para Kennen.

Stage 3: Se você estiver jogando com 3 unidades de Ionia, adicione mais unidades de Ionia ou inclua características relevantes como Demacia, Yordle, Bruisers, etc. Em caso de dúvida, adicione mais unidades de linha de frente.

Stage 4: Procure por Yunara e Wukong no nível 8. Não se esqueça de desbloquear Sett no estágio 4. Assim que estabilizar, vá para o nível 9 buscar as lendárias.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Bel\'Veth (Slayer)',
                'notes' => 'Peças: Qiyana e Briar + colosso, uma das duas 2* (Qiyana é um carry mais forte)
Itens: Slam para Bel\'Veth
Estratégia: Fast 9

Jogue quando você tiver a mutação do Void focada em dano para a Bel\'Veth e conseguir encaixar a Shyvana.

Stage 2: O ideal é abrir com sequência de vitórias com Qiyana 2 > Briar 2 segurando os itens. Você também pode jogar por Ixtal ou Bilgewater para recursos.

Stage 3: Se estiver em win streak, mantenha o ritmo (tempo) e continue fechando itens de forma agressiva. Use as lojas de Bilgewater para conseguir upgrades "gratuitos".

Stage 4: Suba para o nível 8 e role buscando Bel\'Veth 2 + Ambessa + linha de frente. Pegue um augment de combate se puder. Use o restante dos espaços para completar o que estiver faltando. Não force Aatrox 1 se ele não encaixar bem no seu board.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Warwick (Slayer)',
                'notes' => 'Peças: Qiyana e Briar + colosso, uma das duas 2* (Qiyana é um carry mais forte)
Itens: Slam para Warwick
Estratégia: Fast 9

Mantenha sempre 5 Zaun e adapte sua equipe a qualquer tanque de custo 4, seja com 3 Noxus (Swain), 3 Ionia (Wukong), Taric, Juggernauts (Shyvana) ou 7 Zaun. Prioridade para tanques de custo 5 no nível 8: Senna > Kindred > Shyvana. Forte com aprimoramentos de emblema (Pistoleiro > Zaun > Quickstriker / Defensor / Juggernaut).

Stage 2: O ideal é jogar o early game em torno de uma abertura com Briar 2 / Qiyana 2. Você também pode começar com Zaun ou Piltover cedo. O Warwick se beneficia muito de Titan\'s Resolve + Sterak\'s Gage.

Stage 3: Suba para o nível 6 e jogue com 3 Zaun e os seus melhores carregadores já melhorados (prioridade: Vi 2 > Slayer de 1 custo > Ekko 2). Se você estiver em sequência de vitórias, pode subir para o nível 7 mais cedo para liberar o Warwick.

Stage 4: Faça fast 8 e procure por Warwick 2, 5 Zaun e qualquer tank de 4 custos (listado na seção de dicas). A Jinx fica com os itens restantes de AD / AS. 2 Quickstriker não é tão importante até você ter a Kindred.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'Warwick (Zaun)',
                'notes' => 'Peças: Ekko, 3 zaun + tank
Itens: Slam para Warwick
Estratégia: Fast 9

Mantenha sempre 5 Zaun e adapte sua equipe a qualquer tanque de custo 4, seja com 3 Noxus (Swain), 3 Ionia (Wukong), Taric, Juggernauts (Shyvana) ou 7 Zaun. Prioridade para tanques de custo 5 no nível 8: Senna > Kindred > Shyvana. Forte com aprimoramentos de emblema (Pistoleiro > Zaun > Quickstriker / Defensor / Juggernaut).

Stage 2: O ideal é jogar o early game em torno de uma abertura com Briar 2 / Qiyana 2. Você também pode começar com Zaun ou Piltover cedo. O Warwick se beneficia muito de Titan\'s Resolve + Sterak\'s Gage.

Stage 3: Suba para o nível 6 e jogue com 3 Zaun e os seus melhores carregadores já melhorados (prioridade: Vi 2 > Slayer de 1 custo > Ekko 2). Se você estiver em sequência de vitórias, pode subir para o nível 7 mais cedo para liberar o Warwick.

Stage 4: Faça fast 8 e procure por Warwick 2, 5 Zaun e qualquer tank de 4 custos (listado na seção de dicas). A Jinx fica com os itens restantes de AD / AS. 2 Quickstriker não é tão importante até você ter a Kindred.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Bel\'Veth (Void)',
                'notes' => 'Peças: Rek\'Sai
Itens: Slam para Bel\'Veth
Estratégia: Fast 9

Jogue quando você tiver a mutação do Void focada em dano para a Bel\'Veth e conseguir encaixar a Shyvana.

Stage 2: O ideal é abrir com sequência de vitórias com Qiyana 2 > Briar 2 segurando os itens. Você também pode jogar por Ixtal ou Bilgewater para recursos.

Stage 3: Se estiver em win streak, mantenha o ritmo (tempo) e continue fechando itens de forma agressiva. Use as lojas de Bilgewater para conseguir upgrades "gratuitos".

Stage 4: Suba para o nível 8 e role buscando Bel\'Veth 2 + Ambessa + linha de frente. Pegue um augment de combate se puder. Use o restante dos espaços para completar o que estiver faltando. Não force Aatrox 1 se ele não encaixar bem no seu board.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'name' => 'Kindred (Noxus)',
                'notes' => 'Peças: Draven
Itens: Guinsoo ou Kraken\'s
Estratégia: Fast 9

Jogue em torno do Draven como carregador de itens até chegar na Kindred. Os slots de Skarner + Taric são flexíveis. Se você estiver em sequência de derrotas, considere pegar Bard no Stage 2 para farmar rerolls para o Draven mais tarde. Você pode jogar Wardens, Volibear + Bruiser, ou Azir + Shurima. Gunslinger não é importante na Senna.

Stage 2-3: Jogue com augment de economia + Bard para farmar rerolls. Quando estiver rolando atrás do Bard, pegue também alguma trait de economia como Bilgewater, Ixtal ou Ionia de ouro.

Stage 4: Se estiver em sequência de vitórias, faça push de níveis. Se estiver em sequência de derrotas, role no nível 7 no Stage 3-5 para buscar Draven 2 + Bilgewater. Chegue no nível 8 e estabilize com tanks de 4 custo. Jogue com Yunara até conseguir a Kindred.

Stage 4: Vá para o nível 9 e procure por Lucian e Kindred. Evite jogar com linha de frente inteira de 1 custo, porque você precisa segurar o jogo (stall) para a Kindred escalar.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'name' => 'Kalista (Shadow Isles)',
                'notes' => 'Peças: Viego 2*
Itens: Slam para Kalista ou Thresh
Estratégia: Fast 9

Só jogue esse comp se você conseguir um Viego 2 cedo para liberar Shadow Isles e ter alta chance de emendar uma sequência de vitórias. No late game, troque unidades de Shadow Isles (como Yorick e Gwen) por unidades de alto valor. Azir + Shurima é o ideal no late game, mas você também pode procurar por Mel + Ambessa.

Stage 2: Jogue em torno do Viego 2 com itens para manter a win streak. Feche itens de linha de frente no Yorick. Você pode colocar todos os itens de Kalista / Thresh no Viego. Não vai ser o Viego "perfeito", mas foque em máxima força do tabuleiro.

Stage 3: O Viego 2 tende a perder força com o tempo, então faça a transição para outros carregadores já melhorados que possam segurar os itens, sempre que possível. Sempre feche itens ("slam") assim que conseguir os componentes. Encaixe Piltover quando der.

Stage 4: Vá para o nível 8 e procure por Kalista 2 com um carregador Disruptor 2* de AP (como Malzahar ou Seraphine). Adicione Wardens conforme necessário. O Ornn pega os artefatos e componentes que sobrarem.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'name' => 'Kindred (Bilgewater)',
                'notes' => 'Peças: Illaoi ou Shen 2* + bilge
Itens: Qualquer slam no TF pra liberar o graves
Estratégia: Fast 9. 

7 bilge no mid game depois descer para 5 com 2 vidas 
Roll no 8 para bater 7 bilge + MF (pref 2*)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 9,
                'name' => 'Kai\'Sa (Void)',
                'notes' => 'Peças: Kog 2* + Void
Itens: Slam para Kai\'Sa, seja AD ou AP
Estratégia: Fast 9

Melhor mutação de Void (BIS): Leeching Nucleus = Spines >> Adrenaline Modules. Para tank: Iron Carapace >> Husk. O exemplo mostra Kai\'Sa AP, mas você também pode jogar full AD (Shojin + IE + Last Whisper). A linha de frente é flexível: 4 Bruisers com Volibear é o padrão, mas 4 Juggernauts com Shyvana tem teto (cap) maior.

Stage 2: Jogue em torno de Void cedo com Kog\'Maw e mire em uma sequência de vitórias. Priorize montar sua frontline antes de ativar outras traits do Kog. A prioridade de trait é Arcanist > Longshot.

Stage 3: Se você estiver muito à frente na win streak, considere subir para o nível 7 antes do carrossel para "travar" (lock) a Kai\'Sa. Pegue Rift Herald no 3-5 e faça a transição de itens imediatamente com 4 Void. Faça economia mirando em um Fast 8.

Stage 4: Procure por Kai\'Sa 2 e um tank de 4 custo 2*. Se você não conseguir Rift Herald 2, busque alternativas melhoradas, como Bruisers 3 custo ou Juggernauts. Depois de estabilizar, faça push para o nível 9 buscando Ziggs.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 10,
                'name' => 'Vayne (Demacia)',
                'notes' => 'Peças: Vayne
Itens: Kraken\'s ou Gunblade
Estratégia: Fast 9 (Roll no 8 para bater os custo 4) (NÃO É PRA FECHAR A VAYNE 3*)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 11,
                'name' => 'Lux (Demacia)',
                'notes' => 'Peças: Sona 2*, Demacia
Itens: Slam para Lux
Estratégia: Fast 8

Lux é o carry principal, mas procure um segundo carry de Invoker AP em dupla: Orianna > Lissandra > Zilean. Você também pode jogar para Ziggs no late game. Priorize itens de tank "de verdade" no Garen. Se conseguir emblema de Demacia, tire Xin Zhao / Poppy e coloque na frontline (Shyvana / Swain).

Stage 2: Jogue em torno de Demacia já melhorado no early para manter win streak. Poppy (Poppy 1 com Juggernaut > Jarvan 2) é um ótimo tank cedo, então priorize liberar ela o quanto antes.

Stage 3: Adicione mais Demacia conforme for subindo de nível. O setup mais comum é 5 Demacia + 2 Invoker. Procure encaixar Piltover se possível com Orianna + Vi (Blast Shield > Continuum Cogs).

Stage 4: Faça fast 8 no 4-2 e libere o Galio o quanto antes. Não pare de rolar até conseguir Galio e uma frontline melhorada. Depois de estabilizar, vá para o nível 9 buscando Zilean / 5 costs.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 12,
                'name' => 'Seraphine (Piltover)',
                'notes' => 'Peças: Orianna
Itens: Slam para Seraphine ou Lissandra
Estratégia: Fast 8

Na prática, você dificilmente vai conseguir itens BIS de AP, então feche ("slam") o que vier para manter a win streak. As builds mostradas em Seraphine e Lissandra são exemplos de conjuntos de itens AP para usar. Augments de itens são prioridade alta, para você chegar no fim do Stage 4 com 2 carries totalmente itemizados + 1 tank. O pacote de Shurima é flexível e pode ser substituído por qualquer coisa que dê frontline: Taric + Shyvana + Swain, Skarner, Wardens, etc.

Stage 2: Jogue em torno de Orianna e 2 ou 4 Piltover. Freljord cedo e Demacia são boas combinações. Priorize Continuum Cogs > Blast Shield. Você também pode abrir com LeBlanc + Sion.

Stage 3: Se você estiver jogando Piltover, busque 4 Piltover para Acceleration Gate. Caso contrário, vá adicionando mais frontline / invokers enquanto faz economia.

Stage 4: Vá para o nível 8 e role buscando Lissandra, Seraphine e Braum. Coloque todos os itens no carry 2* que você conseguir primeiro. Você tem 2 slots flex até chegar no Azir, então jogue em torno de Wardens, Targons, Skarner, etc.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('compositions')->insert($compositions);
    }
}
