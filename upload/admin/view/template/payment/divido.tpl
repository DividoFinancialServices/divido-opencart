<?php echo $header; ?>
<?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-free-checkout" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-divido" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="api_key"><?php echo $entry_api_key; ?></label>
                        <div class="col-sm-10">
                            <input id="api_key" class="form-control" type="text" name="divido_api_key" value="<?php echo $divido_api_key; ?>" size="60"></td>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_status" id="divido_status" class="form-control">
                                <?php if ($divido_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_order_status_id"><?php echo $entry_order_status; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_order_status_id" id="divido_order_status_id" class="form-control">
                                <?php  foreach ($order_statuses as $order_status): ?>
                                <?php $selected = ($order_status['order_status_id'] == $divido_order_status_id) ? 'selected' : ''; ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" <?php echo $selected; ?>>
                                <?php echo $order_status['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="sort_order"><?php echo $entry_sort_order; ?></label>
                        <div class="col-sm-10">
                            <input type="text" id="divido_sort_order" class="form-control" name="divido_sort_order" value="<?php echo $divido_sort_order; ?>" size="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_title"><?php echo $entry_title; ?></label>
                        <div class="col-sm-10">
                            <input type="text" id="divido_title" name="divido_title" value="<?php echo $divido_title; ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_description"><?php echo $entry_description; ?></label>
                        <div class="col-sm-10">
                            <textarea id="divido_description" class="form-control" name="divido_description"><?php echo $divido_description; ?></textarea>
                        </div>
                    </div>

                    <!--
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_prependtoprice"><?php echo $entry_prependtoprice; ?></label>
                        <div class="col-sm-10">
                            <input type="text" id="divido_prependtoprice" class="form-control" name="divido_prependtoprice" value="<?php echo $divido_prependtoprice; ?>" >
                        </div>
                    </div>
                    -->

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_exclusive"><?php echo $entry_exclusive; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_exclusive" id="divido_exclusive" class="form-control">
                                <?php foreach ($entry_exclusive_options as $option => $text): $selected = $option == $divido_exclusive ? 'selected' : null; ?>
                                <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!--
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_calc_layout"><?php echo $entry_calc_layout; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_calc_layout" id="divido_calc_layout" class="form-control">
                                <?php foreach ($entry_calc_options as $option => $text): $selected = $option == $divido_calc_layout ? 'selected' : null; ?>
                                <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    -->

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_productselection"><?php echo $entry_productselection; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_productselection" id="divido_productselection" class="form-control">
                                <?php foreach ($entry_products_options as $option => $text): $selected = $option == $divido_productselection ? 'selected' : null; ?>
                                <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="threshold" class="form-group">
                        <label class="col-sm-2 control-label" for="divido_price_threshold"><?php echo $entry_price_threshold; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="divido_price_threshold" value="<?php echo $divido_price_threshold; ?>" class="form-control" id="divido_price_threshold">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="divido_planselection"><?php echo $entry_planselection; ?></label>
                        <div class="col-sm-10">
                            <select name="divido_planselection" id="divido_planselection" class="form-control">
                                <?php foreach ($entry_plans_options as $option => $text): $selected = $option == $divido_planselection ? 'selected' : null; ?>
                                <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="plan-list" class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_planlist; ?></label>
                        <div class="col-sm-10">
                            <?php foreach ($divido_plans as $plan): $checked = in_array($plan->id, $divido_plans_selected) ? 'checked' : null; ?>
                            <label>
                                <input type="checkbox" name="divido_plans_selected[]" value="<?php echo $plan->id; ?>" <?php echo $checked; ?>>
                                <?php echo "{$plan->text} ({$plan->interest_rate}% APR)"; ?>
                            </label><br>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function($) {
    var divido = {
        initialize: function () {
            this.bindEvents();
            this.toggleFields();
        },

        bindEvents: function () {
            $('#divido_productselection').on('change', this.toggleFields);
            $('#divido_planselection').on('change', this.toggleFields);

        },

        toggleFields: function () {
            var $apiKeyField = $('#api_key');

            if ($apiKeyField.val().length < 1) {
                $apiKeyField.closest('.form-group').siblings().hide();
            }

            var productSelection = $('#divido_productselection').val();
            var $threshold       = $('#threshold');
            if (productSelection == 'threshold') {
                $threshold.show();
            } else {
                $threshold.hide();
            }

            var planSelection = $('#divido_planselection').val();
            var $planList     = $('#plan-list');
            if (planSelection == 'selected') {
                $planList.show();
            } else {
                $planList.hide();
            }
        }
    };

    $(function () {
        divido.initialize();
    });

})(jQuery);
</script>
<?php echo $footer; ?>
