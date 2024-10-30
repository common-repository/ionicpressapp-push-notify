<div class="wrap">

    <div id="icon-users" class="icon32"><br/></div>
    <h2>Registered users</h2>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $list_table->display() ?>
    </form>

</div>
