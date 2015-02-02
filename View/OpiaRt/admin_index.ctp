<?php
//Include CSS
echo $this->Html->css('OpiaRt.opia_rt.css', array('inline' => true));
?>

<div class="row">
    <div class="col-lg-12">
        <h2>Status</h2>

        <div class="row">
            <div class="col-lg-3">
                <span class="">
                    GTFS RT
                    ::
                     <span
                         class="label label-status <?php echo $api_status['gtfs_rt'] ? 'label-success fui-triangle-up ' : 'label-danger fui-triangle-down ' ?>">
                          <b> <?php echo $api_status['gtfs_rt'] ? 'UP!' : 'DOWN!'; ?></b>
                    </span>
                </span>
            </div>

            <div class="col-lg-3">

            </div>

            <div class="col-lg-3">

            </div>

            <div class="col-lg-3">

            </div>
        </div>

    </div>

</div>


<div class="row">
    <div class="col-lg-12">
        <h2>RT Cache</h2>

        <div class="row">
            <div class="col-lg-12">
                <p>Save to DB: <b><?php echo $rt_records['rt_save_to_db'] ? 'Yes' : 'No!'; ?> </b></p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <p>Database contains: <b><?php echo $rt_records['rt_record_count'] ?> rows</b></p>
            </div>

        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <h2>Log View</h2>
    </div>
    <div class="col-lg-12 log-view-list">
        <ul class="list-group">
            <?php
            foreach ($log_tail as $lti => $ltd) {
                $li_list_class = 'list-group-item-info';
                $list_class = 'text-info';


                if (stripos($ltd, 'info') !== false) {
                    $list_class = 'text-info';
                    $li_list_class = 'list-group-item-success';
                }
                if (stripos($ltd, 'warning') !== false) {
                    $list_class = 'text-warning';
                    $li_list_class = 'list-group-item-warning';
                }
                ?>
                <li class="list-group-item <?php echo $li_list_class ?> ">
                    <span class="<?php echo $list_class ?>"><?php echo $ltd; ?></span>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>
</div>