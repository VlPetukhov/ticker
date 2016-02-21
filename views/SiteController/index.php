<?php
/**
 * Index view
 * @var string $periodName
 * @var array $yahooData
 * @var array $btceData
 */

?>

<h2>Yahoo average currency data for period of <?=$periodName;?></h2>
<table class="table-bordered table-hover">
    <tr>
        <th>Data</th>
        <th>Name</th>
        <th>Ask</th>
        <th>Bid</th>
    </tr>
    <?php foreach($yahooData as $entree): ?>
    <tr>
        <td><?=date('d-m-Y H:i:s', $entree['ts']);?></td>
        <td><?=$entree['name'];?></td>
        <td><?=$entree['ask'];?></td>
        <td><?=$entree['bid'];?></td>
    </tr>
    <?php endforeach;?>
</table>

<h2>BTCe average currency data for period of <?=$periodName;?></h2>
<table class="table-bordered table-hover">
    <tr>
        <th>Data</th>
        <th>Name</th>
        <th>Ask</th>
        <th>Bid</th>
        <th>High</th>
        <th>Low</th>
        <th>Average val</th>
        <th>Vol</th>
        <th>Vol_cur</th>
    </tr>
    <?php foreach($btceData as $entree): ?>
    <tr>
        <td><?=date('d-m-Y H:i:s', $entree['ts']);?></td>
        <td><?=$entree['name'];?></td>
        <td><?=$entree['ask'];?></td>
        <td><?=$entree['bid'];?></td>
        <td><?=$entree['high'];?></td>
        <td><?=$entree['low'];?></td>
        <td><?=$entree['avg_val'];?></td>
        <td><?=$entree['vol'];?></td>
        <td><?=$entree['vol_cur'];?></td>
    </tr>
    <?php endforeach;?>
</table>