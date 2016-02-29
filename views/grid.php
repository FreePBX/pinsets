<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
$dataurl = "ajax.php?module=pinsets&command=getJSON&jdata=grid";
?>
<div id="toolbar-all">
	<a href="?display=pinsets&view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add Pinset")?></a>
</div>
 <table id="pinsets"
        data-url="<?php echo $dataurl?>"
        data-cache="false"
        data-state-save="true"
        data-state-save-id-table="pinsets_grid"
        data-toolbar="#toolbar-all"
        data-maintain-selected="true"
        data-show-columns="true"
        data-show-toggle="true"
        data-toggle="table"
        data-pagination="true"
        data-search="true"
        class="table table-striped">
    <thead>
      <tr>
        <th data-field="description"><?php echo _("Pinset")?></th>
        <th data-field="pinsets_id" data-formatter="linkFormatter" class="col-sm-2"><?php echo _("Actions")?></th>
      </tr>
    </thead>
</table>
<script type="text/javascript">
function linkFormatter(value, row, index){
    var html = '<a href="?display=pinsets&view=form&itemid='+value+'"><i class="fa fa-pencil"></i></a>';
    html += '&nbsp;<a href="?display=pinsets&action=delete&itemid='+value+'" class="delAction"><i class="fa fa-trash"></i></a>';
    return html;
}
</script>
