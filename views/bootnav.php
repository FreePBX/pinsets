<div id="toolbar-bnav">
  <a href="?display=pinsets&view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add Pin Sets")?></a>
  <a href="?display=pinsets" class="btn btn-default"><i class="fa fa-list"></i>&nbsp;<?php echo _("List Pin Sets")?></a>
</div>
<table data-url="ajax.php?module=pinsets&amp;command=getJSON&amp;jdata=grid" data-toolbar="#toolbar-bnav" data-cache="false" data-toggle="table" data-search="true" class="table" id="pinset-all-side">
    <thead>
        <tr>
            <th data-sortable="true" data-field="pinsets_id" data-formatter="descFormatter"><?php echo _('Pin Sets')?></th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
	$("#pinset-all-side").on('click-row.bs.table',function(e,row,elem){
		window.location = '?display=pinsets&view=form&itemid='+row['pinsets_id'];
	})
  function descFormatter(v,r,i){
    return r['description']
  }
</script>
