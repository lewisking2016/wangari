<?php
/**
 * Wangari Help Tooltip System
 * Adds ? icons and hover tooltips explaining farm terms in simple language.
 * Include this file in any admin page: include __DIR__ . '/includes/help_tooltips.php';
 */
?>
<style>
/* ══════ HELP ? ICON ══════ */
.wangari-help {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #E2E8F0;
    color: #64748B;
    font-size: 11px;
    font-weight: 700;
    cursor: help;
    position: relative;
    vertical-align: middle;
    margin-left: 6px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.wangari-help:hover {
    background: #22C55E;
    color: #fff;
    transform: scale(1.15);
}

/* ══════ TOOLTIP BOX ══════ */
.wangari-help-tip {
    display: none;
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    background: #0F172A;
    color: #F8FAFC;
    font-size: 0.82rem;
    font-weight: 400;
    line-height: 1.5;
    padding: 10px 14px;
    border-radius: 10px;
    width: 260px;
    max-width: 300px;
    z-index: 9999;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    pointer-events: none;
    text-align: left;
    word-wrap: break-word;
}
.wangari-help-tip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #0F172A;
}
.wangari-help:hover .wangari-help-tip {
    display: block;
}

/* ══════ TOOLTIP ON HOVER WORDS ══════ */
.wangari-term {
    border-bottom: 1px dashed #94A3B8;
    cursor: help;
    position: relative;
}
.wangari-term:hover {
    color: #22C55E;
    border-bottom-color: #22C55E;
}
.wangari-term .wangari-term-tip {
    display: none;
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #0F172A;
    color: #F8FAFC;
    font-size: 0.8rem;
    line-height: 1.4;
    padding: 8px 12px;
    border-radius: 8px;
    width: 220px;
    max-width: 260px;
    z-index: 9999;
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    pointer-events: none;
    text-align: left;
    word-wrap: break-word;
    font-weight: 400;
    border-bottom: none;
}
.wangari-term .wangari-term-tip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #0F172A;
}
.wangari-term:hover .wangari-term-tip {
    display: block;
}

/* ══════ HELP SECTION HEADER ══════ */
.wangari-section-head {
    display: flex;
    align-items: center;
    gap: 6px;
}
</style>

<script>
/**
 * Auto-apply tooltips to elements with data-wangari-tip attribute.
 * Usage: <span class="wangari-term" data-wangari-tip="Simple explanation here">Technical Word</span>
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add click-to-toggle for mobile (hover doesn't work well on touch)
    document.querySelectorAll('.wangari-help, .wangari-term').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.stopPropagation();
            var tip = this.querySelector('.wangari-help-tip, .wangari-term-tip');
            if (tip) {
                // Close all other tooltips first
                document.querySelectorAll('.wangari-help-tip, .wangari-term-tip').forEach(function(t) {
                    if (t !== tip) t.style.display = 'none';
                });
                tip.style.display = tip.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    // Close tooltips when clicking elsewhere
    document.addEventListener('click', function() {
        document.querySelectorAll('.wangari-help-tip, .wangari-term-tip').forEach(function(t) {
            t.style.display = 'none';
        });
    });
});
</script>

<?php
/**
 * ════════════════════════════════════════════════════════════════
 * HELPER FUNCTIONS — call these in your admin pages
 * ════════════════════════════════════════════════════════════════
 *
 * Quick ? icon:     <?= helpTip('Explanation text here') ?>
 * Section header:   <?= sectionHead('Health Records', 'Treatments, vaccines, and health checks for all animals.') ?>
 * Technical term:   <?= term('mortality', 'When an animal dies. We track how many died and why.') ?>
 *
 * Examples:
 *   <h3>Mortality <?= helpTip('This tracks when animals die and why it happened.') ?></h3>
 *   <p>Your <?= term('layers', 'Female chickens that lay eggs.') ?> are producing well.</p>
 */

function helpTip(string $text): string
{
    return '<span class="wangari-help">?<span class="wangari-help-tip">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span></span>';
}

function term(string $word, string $explanation): string
{
    return '<span class="wangari-term">' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '<span class="wangari-term-tip">' . htmlspecialchars($explanation, ENT_QUOTES, 'UTF-8') . '</span></span>';
}

function sectionHead(string $title, string $help): string
{
    return '<span class="wangari-section-head">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' ' . helpTip($help) . '</span>';
}

/**
 * ════════════════════════════════════════════════════════════════
 * PRE-DEFINED FARM TERMS — use these anywhere in the system
 * ════════════════════════════════════════════════════════════════
 */

// Simplified explanations for common farm terms
function termChicken(): string { return term('chickens', 'Birds kept for eggs or meat. In this system we call them poultry.'); }
function termLayers(): string { return term('layers', 'Female chickens whose job is to lay eggs every day.'); }
function termBroilers(): string { return term('broilers', 'Chickens raised only for their meat. They grow fast and are sold after 6-8 weeks.'); }
function termKienyeji(): string { return term('kienyeji', 'Local/free-range chickens that walk around freely. They lay brown eggs.'); }
function termFlock(): string { return term('flock', 'A group of birds (usually chickens) kept together.'); }
function termHerd(): string { return term('herd', 'A group of large animals like cattle, goats, or sheep kept together.'); }
function termBreed(): string { return term('breed', 'The type or family of an animal. E.g. Holstein is a breed of dairy cow.'); }
function termMortality(): string { return term('mortality', 'When animals die. We track the number and cause of deaths.'); }
function termMortalityRate(): string { return term('mortality rate', 'The percentage of animals that died. A lower number is better.'); }
function termVaccination(): string { return term('vaccination', 'A small injection that protects animals from getting sick. Like a flu jab for people.'); }
function termDeworming(): string { return term('deworming', 'Giving medicine to kill tiny worms inside animals that make them sick.'); }
function termQuarantine(): string { return term('quarantine', 'Keeping a sick animal alone so it doesn\'t make other animals sick.'); }
function termAI(): string { return term('AI (artificial insemination)', 'A way to get a cow pregnant without a bull. A vet puts the sperm in.'); }
function termBodyCondition(): string { return term('body condition', 'How fat or thin an animal looks and feels. Scored 1 (very thin) to 5 (very fat).'); }
function termMilking(): string { return term('milking', 'Getting milk out of a cow, goat, or sheep by hand or machine.'); }
function termFeed(): string { return term('feed', 'The food you give to your animals. Can be grain, grass, or mixed ration.'); }
function termFodder(): string { return term('fodder', 'Dried grass, hay, or chopped plants stored to feed animals in dry seasons.'); }
function termBoma(): string { return term('boma', 'A fenced area or pen where animals sleep and are kept safe at night.'); }
function termKraal(): string { return term('kraal', 'A traditional African enclosure for cattle, goats, or sheep.'); }
function termPasture(): string { return term('pasture', 'An area of grass where animals graze/eat naturally.'); }
function termGrazing(): string { return term('grazing', 'When animals eat grass in the field. Like cattle eating in a meadow.'); }
function termHatchery(): string { return term('hatchery', 'A place where eggs are kept warm so baby chicks can grow and come out.'); }
function termBrooding(): string { return term('brooding', 'Keeping baby chicks warm with a heat lamp until they can survive on their own.'); }
function termCulling(): string { return term('culling', 'Removing weak or sick animals from the group to keep the rest healthy.'); }
function termEggGrading(): string { return term('egg grading', 'Sorting eggs by size and quality: Small, Medium, Large, Extra Large, Jumbo.'); }
function termCrackedEggs(): string { return term('cracked eggs', 'Eggs that have small breaks in the shell. They can\'t be sold as whole eggs.'); }
function termWithdrawalPeriod(): string { return term('withdrawal period', 'The number of days after giving medicine before you can sell the animal or its eggs/milk. This keeps food safe.'); }
function termMilkYield(): string { return term('milk yield', 'How much milk a cow produces. Measured in litres per day.'); }
function termFatPercentage(): string { return term('fat %', 'How much butter fat is in milk. Higher fat = richer milk. Cow milk is about 3.5%.'); }

// Crop terms
function termCrop(): string { return term('crop', 'Any plant you grow on your farm — maize, beans, tomatoes, coffee, etc.'); }
function termPlanting(): string { return term('planting', 'Putting seeds or seedlings into the soil so they can grow.'); }
function termHarvest(): string { return term('harvest', 'Collecting your crops when they are ready. Like picking ripe maize.'); }
function termVariety(): string { return term('variety', 'A specific type of a crop. E.g. H614 is a variety of maize.'); }
function termGermination(): string { return term('germination', 'When a seed starts to grow into a small plant. The % shows how many seeds grew.'); }
function termIrrigation(): string { return term('irrigation', 'Giving water to your crops artificially (not from rain). Using pipes, sprinklers, or canals.'); }
function termDripIrrigation(): string { return term('drip irrigation', 'A watering system that drops water slowly to each plant\'s roots. Saves water.'); }
function termPest(): string { return term('pest', 'An insect or animal that damages your crops. E.g. locusts, caterpillars, aphids.'); }
function termFungicide(): string { return term('fungicide', 'A chemical spray that kills fungi (mushroom-like germs) on plants.'); }
function termHerbicide(): string { return term('herbicide', 'A chemical that kills unwanted weeds growing among your crops.'); }
function termNPK(): string { return term('NPK', 'The three main nutrients plants need: Nitrogen (N) for leaves, Phosphorus (P) for roots, Potassium (K) for overall health.'); }
function termpHLevel(): string { return term('pH level', 'How acidic or alkaline your soil is. Most crops like pH 6.0-7.0 (slightly acidic).'); }
function termCompost(): string { return term('compost', 'Rotten organic matter (leaves, manure, food waste) that becomes natural fertilizer.'); }
function termTopDressing(): string { return term('top dressing', 'Putting fertilizer on top of the soil around growing plants.'); }
function termIntercropping(): string { return term('intercropping', 'Growing two different crops in the same field at the same time. E.g. beans between maize rows.'); }
function termCropRotation(): string { return term('crop rotation', 'Growing different crops in the same field each season to keep the soil healthy.'); }
function termYield(): string { return term('yield', 'How much crop you harvest from a field. Measured in kg, bags, or tonnes.'); }
function termAcre(): string { return term('acre', 'A measurement of land. 1 acre = about 4,047 square metres (roughly a football field).'); }

// Financial terms
function termRevenue(): string { return term('revenue', 'The total money you get from selling your products. Also called income.'); }
function termProfit(): string { return term('profit', 'The money left after you subtract all your costs from your revenue. Revenue minus Costs = Profit.'); }
function termExpense(): string { return term('expense', 'Any money you spend on the farm — feed, medicine, labour, seeds, etc.'); }
function termCashflow(): string { return term('cashflow', 'The movement of money in and out of your farm. Money coming in vs money going out.'); }
function termDepreciation(): string { return term('depreciation', 'How much value your equipment loses over time. A tractor bought for KES 5M may lose KES 500,000 per year.'); }
function termAsset(): string { return term('asset', 'Anything valuable you own on the farm — land, buildings, vehicles, equipment.'); }
function termLiability(): string { return term('liability', 'Money you owe to others — loans, unpaid bills, credit from suppliers.'); }
function termBudget(): string { return term('budget', 'A plan for how much money you expect to earn and spend over a period of time.'); }
function termLPO(): string { return term('LPO (Local Purchase Order)', 'A document you send to a supplier to officially order goods. It shows what you want, how much, and the price.'); }
function termMpesa(): string { return term('M-Pesa', 'A mobile money service in Kenya. You can send and receive money using your phone.'); }
function termWithholdingTax(): string { return term('withholding tax', 'A small amount of tax the buyer takes from your payment and sends to the government on your behalf.'); }
function termVAT(): string { return term('VAT (Value Added Tax)', 'An extra 16% tax added to the price of most goods and services in Kenya.'); }
function termPAndL(): string { return term('P&L (Profit & Loss)', 'A report that shows all your income and all your expenses so you can see if you made a profit or loss.'); }

// Inventory terms
function termSKU(): string { return term('SKU (Stock Keeping Unit)', 'A unique code for each product so you can track it easily. Like a barcode number.'); }
function termStock(): string { return term('stock', 'The amount of goods or materials you have stored on the farm.'); }
function termReorder(): string { return term('reorder point', 'The stock level where you need to buy more before you run out.'); }
function termBatch(): string { return term('batch', 'A group of products made or bought at the same time from the same source.'); }
function termExpiry(): string { return term('expiry date', 'The date when a product (like medicine or seed) is no longer safe or effective to use.'); }
function termFIFO(): string { return term('FIFO', 'First In, First Out. Sell or use the oldest stock first so nothing expires.'); }

// HR terms
function termCasual(): string { return term('casual worker', 'A worker hired for a short time (days or weeks) without a permanent contract.'); }
function termContract(): string { return term('contract worker', 'A worker hired for a fixed period (e.g. 6 months) with a written agreement.'); }
function termPieceRate(): string { return term('piece rate', 'Getting paid for each unit you complete (e.g. KES 5 per egg tray packed) instead of a daily wage.'); }

// Animal product terms
function termEggLoss(): string { return term('egg loss', 'Eggs that break or get damaged during collection, transport, or storage.'); }
function termFeedConversion(): string { return term('feed conversion ratio (FCR)', 'How much feed an animal needs to gain 1 kg of weight. Lower is better. E.g. a broiler needs 1.8 kg feed for 1 kg meat.'); }
function termDayOldChick(): string { return term('day-old chick', 'A baby chicken that just hatched (1 day old). Bought from a hatchery.'); }
function termPointOfLay(): string { return term('point of lay', 'When a young hen is almost ready to start laying eggs (about 18-20 weeks old).'); }
function termCull(): string { return term('cull', 'To remove an animal from the group because it\'s old, sick, or not producing well.'); }

// Weather & environment
function termRelativeHumidity(): string { return term('relative humidity', 'How much water vapor is in the air. High humidity can make animals feel hotter.'); }
function termWindChill(): string { return term('wind chill', 'How cold it feels when wind blows. Animals need extra protection in cold, windy weather.'); }
