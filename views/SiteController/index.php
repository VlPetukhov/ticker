<?php
/**
 * Index view
 * @var array $data
 */

?>

<h2>Yahoo hourly data</h2>
<table>
    <tr>
        <th>Data</th>
        <th>Name</th>
        <th>Ask</th>
        <th>Bid</th>
    </tr>
    <?php foreach($data as $entree): ?>
    <tr>
        <td><?=date('d-m-Y H:i:s', $entree['ts']);?></td>
        <td><?=$entree['name'];?></td>
        <td><?=$entree['ask'];?></td>
        <td><?=$entree['bid'];?></td>
    </tr>
    <?php endforeach;?>
</table>