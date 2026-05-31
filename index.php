<?php
$feedUrl = $_GET['feed_url'] ?? '';
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
$data = null;
$error = null;
$players = [];

if ($feedUrl) {
    if (filter_var($feedUrl, FILTER_VALIDATE_URL)) {
        // Suppress warnings on file_get_contents for clean error handling
        $json = @file_get_contents($feedUrl);
        if ($json === false) {
            $error = "Failed to fetch data from the provided URL. Ensure the URL is accessible.";
        } else {
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = "Invalid JSON format received.";
            } else {
                // Build a global player dictionary for easy lookups
                foreach (['HomeTeam', 'VisitingTeam'] as $teamType) {
                    if (isset($data['Stats'][$teamType]['Roster'])) {
                        foreach ($data['Stats'][$teamType]['Roster'] as $player) {
                            $players[$player['PlayerId']] = $player;
                        }
                    }
                }
            }
        }
    } else {
        $error = "Please provide a valid HTTP/HTTPS URL.";
    }
}

/**
 * Helper function to safely get a player's full name and number.
 */
function getPlayerDisplay($id, $players) {
    if (isset($players[$id])) {
        return "#{$players[$id]['UniformNumber']} {$players[$id]['FirstName']} {$players[$id]['LastName']}";
    }
    return "Unknown Player ($id)";
}

// If this is an AJAX request and there's an error, just return the error text
if ($isAjax && $error) {
    echo "<div class='p-4 text-red-600 font-bold text-center'>Error updating feed: " . htmlspecialchars($error) . "</div>";
    exit;
}
?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkateStats Live Coverage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar for tables */
        .table-container::-webkit-scrollbar { height: 8px; }
        .table-container::-webkit-scrollbar-track { background: #f1f1f1; }
        .table-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-container::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Toggle Switch Styles */
        .toggle-checkbox:checked { right: 0; border-color: #2563eb; }
        .toggle-checkbox:checked + .toggle-label { background-color: #2563eb; }
        .toggle-checkbox:checked + .toggle-label:after { transform: translateX(100%); border-color: white; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">

    <header class="bg-slate-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wider">SKATE<span class="text-blue-400">STATS</span></h1>
            <a href="?" class="text-sm text-slate-300 hover:text-white transition">Load New Feed</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        
        <?php if ($error && !$isAjax): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
                <p class="font-bold">Error</p>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$data): ?>
            <div class="max-w-xl mx-auto mt-12 bg-white rounded-lg shadow-sm border border-slate-200 p-8">
                <h2 class="text-2xl font-bold mb-2 text-center text-slate-800">Load Game Data</h2>
                <p class="text-slate-500 text-center mb-6">Enter the URL of a valid SkateStats JSON feed to view the live dashboard.</p>
                
                <form method="GET" action="" class="space-y-4">
                    <div>
                        <label for="feed_url" class="block text-sm font-medium text-slate-700 mb-1">JSON Feed URL</label>
                        <input type="url" name="feed_url" id="feed_url" required placeholder="https://..." 
                               class="w-full border border-slate-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-700 transition duration-150">
                        Launch Dashboard
                    </button>
                </form>

                <div class="mt-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-slate-500">Or</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="?feed_url=https://demo-skatestats.cgoldstein.xyz/" class="w-full flex justify-center bg-white text-slate-700 font-semibold py-2 px-4 border border-slate-300 rounded-md shadow-sm hover:bg-slate-50 transition duration-150">
                        Load Demo Feed
                    </a>
                </div>
            </div>

        <?php else: ?>
            
            <div class="flex justify-between items-center bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <?php 
                            // Determine if autorefresh should be checked by default (not complete)
                            $autoRefreshDefault = !$data['Game']['IsComplete'] ? 'checked' : '';
                        ?>
                        <input type="checkbox" id="autoRefreshToggle" class="sr-only peer toggle-checkbox" <?= $autoRefreshDefault ?>>
                        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 transition-colors toggle-label after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-transform"></div>
                        <span class="ml-3 text-sm font-semibold text-slate-700">Auto-Refresh Feed</span>
                    </label>
                </div>
                
                <div id="refreshIndicator" class="text-sm font-medium text-slate-400 flex items-center opacity-0 transition-opacity duration-300">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating...
                </div>
            </div>

            <div id="dashboardContainer">
<?php endif; ?>

                <?php 
                if ($data):
                    $game = $data['Game'];
                    $stats = $data['Stats'];
                    $plays = $data['Plays'] ?? [];
                    
                    $home = $game['HomeTeam'];
                    $away = $game['VisitingTeam'];
                ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="grid grid-cols-3 items-center p-6 bg-gradient-to-b from-slate-50 to-white">
                        
                        <div class="flex flex-col items-center">
                            <?php if ($away['LogoUrl']): ?>
                                <img src="<?= htmlspecialchars($away['LogoUrl']) ?>" alt="<?= htmlspecialchars($away['Name']) ?>" class="h-20 w-20 object-contain mb-3">
                            <?php endif; ?>
                            <h2 class="text-xl font-bold text-center uppercase" style="color: <?= htmlspecialchars($away['ColorPrimary'] ?? '#000') ?>">
                                <?= htmlspecialchars($away['Name']) ?>
                            </h2>
                            <span class="text-slate-500 text-sm">(<?= htmlspecialchars($away['Record'] ?? '0-0-0') ?>)</span>
                        </div>

                        <div class="text-center flex flex-col justify-center border-x border-slate-100 px-4">
                            <div class="text-xs uppercase tracking-widest text-slate-400 font-semibold mb-2">
                                <?= htmlspecialchars($game['Location']) ?> &bull; <?= htmlspecialchars($game['Date']) ?>
                            </div>
                            <div class="flex justify-center items-center space-x-6 mb-2">
                                <span class="text-6xl font-black text-slate-800"><?= $away['Score'] ?></span>
                                <span class="text-slate-300 text-2xl">-</span>
                                <span class="text-6xl font-black text-slate-800"><?= $home['Score'] ?></span>
                            </div>
                            <div class="inline-block bg-slate-900 text-white px-4 py-1 rounded-full text-sm font-bold tracking-wide mx-auto mt-2">
                                <?php if ($game['IsComplete']): ?>
                                    FINAL
                                <?php else: ?>
                                    P<?= $game['CurrentPeriod'] ?> | 
                                    <?= floor($game['ClockSecondsRemaining'] / 60) ?>:<?= str_pad($game['ClockSecondsRemaining'] % 60, 2, '0', STR_PAD_LEFT) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex flex-col items-center">
                            <?php if ($home['LogoUrl']): ?>
                                <img src="<?= htmlspecialchars($home['LogoUrl']) ?>" alt="<?= htmlspecialchars($home['Name']) ?>" class="h-20 w-20 object-contain mb-3">
                            <?php endif; ?>
                            <h2 class="text-xl font-bold text-center uppercase" style="color: <?= htmlspecialchars($home['ColorPrimary'] ?? '#000') ?>">
                                <?= htmlspecialchars($home['Name']) ?>
                            </h2>
                            <span class="text-slate-500 text-sm">(<?= htmlspecialchars($home['Record'] ?? '0-0-0') ?>)</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-8">
                        
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Team Statistics</h3>
                            <table class="w-full text-sm text-center">
                                <thead class="text-slate-500 border-b">
                                    <tr>
                                        <th class="py-2 w-1/3"><?= htmlspecialchars($away['Id']) ?></th>
                                        <th class="py-2 w-1/3">Stat</th>
                                        <th class="py-2 w-1/3"><?= htmlspecialchars($home['Id']) ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php 
                                        $compareStats = [
                                            'ShotsOnGoal' => 'Shots on Goal',
                                            'PowerPlayEfficiency' => 'Power Play',
                                            'PenaltyMinutes' => 'Penalty Minutes',
                                            'FaceoffsWon' => 'Faceoffs Won'
                                        ];
                                        foreach ($compareStats as $key => $label): 
                                    ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="py-3 font-semibold"><?= htmlspecialchars($stats['VisitingTeam']['TeamTotals'][$key] ?? '-') ?></td>
                                        <td class="py-3 text-slate-500 uppercase text-xs tracking-wide"><?= $label ?></td>
                                        <td class="py-3 font-semibold"><?= htmlspecialchars($stats['HomeTeam']['TeamTotals'][$key] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php foreach (['VisitingTeam' => $away['Name'], 'HomeTeam' => $home['Name']] as $teamKey => $teamName): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                                <div class="bg-slate-100 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
                                    <h3 class="font-bold text-slate-800"><?= htmlspecialchars($teamName) ?> Box Score</h3>
                                </div>
                                
                                <div class="p-0 table-container overflow-x-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Skater</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Goals">G</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Assists">A</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Points">PTS</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Shots on Goal">SOG</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Plus/Minus">+/-</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Penalty Minutes">PIM</th>
                                                <th class="px-3 py-3 font-semibold text-center" title="Faceoffs Won-Lost">FOW-L</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php if(isset($stats[$teamKey]['Skaters'])): foreach ($stats[$teamKey]['Skaters'] as $skater): ?>
                                                <tr class="hover:bg-blue-50/50 transition-colors">
                                                    <td class="px-4 py-2 font-medium text-slate-800 whitespace-nowrap">
                                                        <?= getPlayerDisplay($skater['PlayerId'], $players) ?>
                                                    </td>
                                                    <td class="px-3 py-2 text-center"><?= $skater['Goals'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= $skater['Assists'] ?></td>
                                                    <td class="px-3 py-2 text-center font-semibold"><?= $skater['Points'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= $skater['ShotsOnGoal'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= $skater['PlusMinus'] > 0 ? '+'.$skater['PlusMinus'] : $skater['PlusMinus'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= $skater['PenaltyMinutes'] ?></td>
                                                    <td class="px-3 py-2 text-center text-xs text-slate-500"><?= $skater['FaceoffsWon'] ?>-<?= $skater['FaceoffsLost'] ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="bg-slate-50 px-4 py-2 border-y border-slate-200">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Goaltenders</h4>
                                </div>
                                <div class="p-0 table-container overflow-x-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-white text-slate-500 text-xs border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-2 font-semibold">Goalie</th>
                                                <th class="px-3 py-2 font-semibold text-center" title="Saves">SV</th>
                                                <th class="px-3 py-2 font-semibold text-center" title="Goals Allowed">GA</th>
                                                <th class="px-3 py-2 font-semibold text-center" title="Save Percentage">SV%</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php if(isset($stats[$teamKey]['Goalies'])): foreach ($stats[$teamKey]['Goalies'] as $goalie): ?>
                                                <tr class="hover:bg-blue-50/50 transition-colors">
                                                    <td class="px-4 py-2 font-medium text-slate-800 whitespace-nowrap">
                                                        <?= getPlayerDisplay($goalie['PlayerId'], $players) ?>
                                                    </td>
                                                    <td class="px-3 py-2 text-center"><?= $goalie['Saves'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= $goalie['GoalsAllowed'] ?></td>
                                                    <td class="px-3 py-2 text-center"><?= number_format($goalie['SavePercentage'], 3) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                    <div class="space-y-8">
                        
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">Top Performers</h3>
                            <?php foreach (['Points', 'Goals'] as $leaderCat): ?>
                                <h4 class="text-sm font-bold text-slate-500 uppercase mb-2 mt-4"><?= $leaderCat ?> Leaders</h4>
                                <ul class="space-y-3">
                                    <?php 
                                        // Combine and sort leaders from both teams (simplified logic for demonstration)
                                        $allLeaders = [];
                                        if (isset($data['Leaders']['HomeTeam'][$leaderCat])) {
                                            foreach($data['Leaders']['HomeTeam'][$leaderCat] as $l) { $l['Team'] = $home['Id']; $allLeaders[] = $l; }
                                        }
                                        if (isset($data['Leaders']['VisitingTeam'][$leaderCat])) {
                                            foreach($data['Leaders']['VisitingTeam'][$leaderCat] as $l) { $l['Team'] = $away['Id']; $allLeaders[] = $l; }
                                        }
                                        usort($allLeaders, fn($a, $b) => $b['Value'] <=> $a['Value']);
                                        $topLeaders = array_slice($allLeaders, 0, 3);
                                        foreach ($topLeaders as $leader):
                                    ?>
                                        <li class="flex justify-between items-center text-sm border-l-2 pl-3 
                                            <?= $leader['Team'] == $home['Id'] ? 'border-blue-500' : 'border-slate-800' ?>">
                                            <div>
                                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($leader['Name']) ?></span>
                                                <span class="text-xs text-slate-400 ml-1">(<?= $leader['Team'] ?>)</span>
                                            </div>
                                            <div class="font-bold bg-slate-100 px-2 py-0.5 rounded text-slate-700"><?= $leader['Value'] ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endforeach; ?>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col" style="max-height: 800px;">
                            <div class="p-4 border-b border-slate-200 bg-slate-50 rounded-t-lg">
                                <h3 class="text-lg font-bold text-slate-800">Play-By-Play</h3>
                            </div>
                            
                            <div class="overflow-y-auto p-0 flex-grow table-container">
                                <?php if (empty($plays)): ?>
                                    <p class="p-6 text-center text-slate-500 text-sm">No plays recorded yet.</p>
                                <?php else: ?>
                                    <div class="divide-y divide-slate-100">
                                        <?php foreach (array_reverse($plays) as $play): ?>
                                            <div class="p-4 hover:bg-slate-50 transition-colors flex gap-4 
                                                <?= $play['Type'] === 'Goal' ? 'bg-green-50/50' : '' ?>">
                                                
                                                <div class="flex-shrink-0 text-center w-12">
                                                    <div class="text-xs font-bold text-slate-800">P<?= $play['Period'] ?></div>
                                                    <div class="text-xs text-slate-500"><?= $play['ClockTime'] ?></div>
                                                </div>
                                                
                                                <div>
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="text-xs font-bold px-2 py-0.5 rounded 
                                                            <?= $play['TeamId'] === $home['Id'] ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800' ?>">
                                                            <?= htmlspecialchars($play['TeamId']) ?>
                                                        </span>
                                                        <span class="text-xs font-semibold uppercase tracking-wider 
                                                            <?= $play['Type'] === 'Goal' ? 'text-green-600' : 'text-slate-500' ?>">
                                                            <?= htmlspecialchars($play['Type']) ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-slate-700 leading-snug">
                                                        <?= htmlspecialchars($play['Narrative']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endif; // End of $data check inside dashboard container ?>

<?php if (!$isAjax): ?>
            </div> <?php endif; ?>

    </main>

<?php if (!$isAjax): ?>
    
    <?php if ($data): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggle = document.getElementById('autoRefreshToggle');
            const indicator = document.getElementById('refreshIndicator');
            const container = document.getElementById('dashboardContainer');
            const feedUrl = <?= json_encode($feedUrl) ?>;
            
            // Poll every 10 seconds
            setInterval(async () => {
                if (toggle && toggle.checked) {
                    
                    // Show updating indicator
                    indicator.classList.remove('opacity-0');
                    
                    try {
                        const response = await fetch(`?feed_url=${encodeURIComponent(feedUrl)}&ajax=1`);
                        if (response.ok) {
                            const html = await response.text();
                            // Update the container silently
                            container.innerHTML = html;
                        } else {
                            console.error('Server returned an error status during refresh');
                        }
                    } catch (e) {
                        console.error('Failed to fetch data for auto-refresh', e);
                    } finally {
                        // Hide indicator after brief delay to assure the user something happened
                        setTimeout(() => {
                            indicator.classList.add('opacity-0');
                        }, 800);
                    }
                }
            }, 10000);
        });
    </script>
    <?php endif; ?>

</body>
</html>
<?php endif; ?>
<?php 
// Terminate script cleanly if it was an AJAX hit
if ($isAjax) exit; 
?>
