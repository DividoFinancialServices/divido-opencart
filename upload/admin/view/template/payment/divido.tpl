<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb): ?>
        <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($error_warning): ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php endif; ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>

    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <div id="tab-general" class="page">
          <table class="form">

            <tr>
              <td><?php echo $entry_api_key ; ?></td>
              <td><input id="api_key" type="text" name="divido_api_key" value="<?php echo $divido_api_key; ?>" size="60"></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_status; ?></td>
              <td>
                <select name="divido_status">
                <?php if ($divido_status) { ?>
                  <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                  <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                  <option value="1"><?php echo $text_enabled; ?></option>
                  <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
                </select>
              </td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_order_status; ?></td>
              <td>
                <select name="divido_order_status_id">
                <?php  foreach ($order_statuses as $order_status): ?>
                    <?php $selected = ($order_status['order_status_id'] == $divido_order_status_id) ? 'selected' : ''; ?>
                    <option value="<?php echo $order_status['order_status_id']; ?>" <?php echo $selected; ?>>
                        <?php echo $order_status['name']; ?>
                    </option>
                <?php endforeach; ?>
                </select>
              </td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_sort_order; ?></td>
              <td><input type="text" name="divido_sort_order" value="<?php echo $divido_sort_order; ?>" size="1" /></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_title ; ?></td>
              <td><input type="text" name="divido_title" value="<?php echo $divido_title; ?>" ></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_description ; ?></td>
              <td><textarea name="divido_description"><?php echo $divido_description; ?></textarea></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_prependtoprice ; ?></td>
              <td><input type="text" name="divido_prependtoprice" value="<?php echo $divido_prependtoprice; ?>" ></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_calc_layout ; ?></td>
              <td>
                <select name="divido_calc_layout">
                <?php foreach ($entry_calc_options as $option => $text): $selected = $option == $divido_calc_layout ? 'selected' : null; ?>
                  <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                <?php endforeach; ?>
                </select>
              </td>
              
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_productselection ; ?></td>
              <td>
                <select name="divido_productselection">
                <?php foreach ($entry_products_options as $option => $text): $selected = $option == $divido_productselection ? 'selected' : null; ?>
                  <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                <?php endforeach; ?>
                </select>
              </td>
            </tr>

            <tr id="price_threshold" class="hidden">
              <td><?php echo $entry_price_threshold ; ?></td>
              <td><input type="text" name="divido_price_threshold" value="<?php echo $divido_price_threshold; ?>" ></td>
            </tr>

            <tr class="hidden">
              <td><?php echo $entry_planselection ; ?></td>
              <td>
                <select name="divido_planselection">
                <?php foreach ($entry_plans_options as $option => $text): $selected = $option == $divido_planselection ? 'selected' : null; ?>
                  <option value="<?php echo $option; ?>" <?php echo $selected; ?>><?php echo $text; ?></option>
                <?php endforeach; ?>
                </select>
              </td>
            </tr>

            <tr id="plan_list" class="hidden">
              <td valign="top"><?php echo $entry_planlist ; ?></td>
              <td>
              <?php foreach ($divido_plans as $plan): $checked = in_array($plan->id, $divido_plans_selected) ? 'checked' : null; ?>
                <label>
                  <input type="checkbox" name="divido_plans_selected[]" value="<?php echo $plan->id; ?>" <?php echo $checked; ?>>
                  <?php echo "{$plan->text} ({$plan->interest_rate}% APR)"; ?>
                </label><br>
              <?php endforeach; ?>
              </td>
            </tr>

          </table>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function($) {
    if ($('#api_key').val() == '') {
        $('tr.hidden').hide();
    }

})(jQuery);
</script>
<?php echo $footer; ?>
