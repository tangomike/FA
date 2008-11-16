<?php

$path_to_root="..";
$page_security = 8;

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$view_id = find_submit('view');
if ($view_id != -1)
{
	$row = get_attachment($view_id);
	if ($row['filename'] != "")
	{
		$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
    	header("Content-type: ".$type);
    	header('Content-Length: '.$row['filesize']);
    	if ($type == 'application/octet-stream')
    		header('Content-Disposition: attachment; filename='.$row['filename']);
    	else
	 		header("Content-Disposition: inline");
    	echo $row["bin_data"];
    	exit();
	}	
}

$download_id = find_submit('download');
if ($download_id != -1)
{
	$row = get_attachment($download_id);
	if ($row['filename'] != "")
	{
		$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';	
    	header("Content-type: ".$type);
    	header('Content-Length: '.$row['filesize']);
    	header('Content-Disposition: attachment; filename='.$row['filename']);
    	echo $row["bin_data"];
    	exit();
	}	
}

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
page(_("Attach Documents"), false, false, "", $js);

simple_page_mode(true);
//----------------------------------------------------------------------------------------
if (isset($_GET['filterType'])) // catch up external links
	$_POST['filterType'] = $_GET['filterType'];
if (isset($_GET['trans_no']))
	$_POST['trans_no'] = $_GET['trans_no'];
	
if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM')
{
	if (isset($_FILES['filename']) && $_FILES['filename']['size'] > 0)
	{
		//$content = base64_encode(file_get_contents($_FILES['filename']['tmp_name']));
		$tmpname = $_FILES['filename']['tmp_name'];
		$fp      = fopen($tmpname, 'r');
		$content = fread($fp, filesize($tmpname));
		$content = addslashes($content);
		fclose($fp);

		//$content = addslashes(file_get_contents($_FILES['filename']['tmp_name']));
		$filename = $_FILES['filename']['name'];
		$filesize = $_FILES['filename']['size'];
		$filetype = $_FILES['filename']['type'];
	}
	else
	{
		$content = $filename = $filetype = "";
		$filesize = 0;
	}
	$date = date2sql(Today());
	if ($Mode == 'ADD_ITEM')
	{
		$sql = "INSERT INTO ".TB_PREF."attachments (type_no, trans_no, description, bin_data, filename,
			filesize, filetype, tran_date) VALUES (".$_POST['filterType'].",".$_POST['trans_no'].",".
			db_escape($_POST['description']).",'$content', '$filename', '$filesize', '$filetype', '$date')";
		db_query($sql, "Attachment could not be inserted");		
		display_notification(_("Attachment has been inserted.")); 
	}
	else
	{
		$sql = "UPDATE ".TB_PREF."attachments SET
			type_no=".$_POST['filterType'].",
			trans_no=".$_POST['trans_no'].",
			description=".db_escape($_POST['description']).", ";
		if ($filename != "")
		{
			$sql .= "bin_data='$content',
			filename='$filename',
			filesize='$filesize',
			filetype='$filetype', ";
		}	
		$sql .= "tran_date='$date' WHERE id=$selected_id";
		db_query($sql, "Attachment could not be updated");		
		display_notification(_("Attachment has been updated.")); 
	}
	$Mode = 'RESET';
}		

if ($Mode == 'Delete')
{
	$sql = "DELETE FROM ".TB_PREF."attachments WHERE id = $selected_id";
	db_query($sql, "Could not delete attachment");
	display_notification(_("Attachment has been deleted.")); 
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	unset($_POST['trans_no']);
	unset($_POST['description']);
	$selected_id = -1;
}

function viewing_controls()
{
    start_form(false, true);

    start_table("class='tablestyle_noborder'");

	systypes_list_row(_("Type:"), 'filterType', null, true);

    end_table(1);

	end_form();
}

//----------------------------------------------------------------------------------------

function get_attached_documents($type)
{
	$sql = "SELECT * FROM ".TB_PREF."attachments WHERE type_no=$type ORDER BY trans_no";
	return db_query($sql, "Could not retrieve attachments");
}

function get_attachment($id)
{
	$sql = "SELECT * FROM ".TB_PREF."attachments WHERE id=$id";
	$result = db_query($sql, "Could not retrieve attachments");
	return db_fetch($result);
}

function display_rows($type)
{
	global $table_style;

	$rows = get_attached_documents($type);
	$th = array(_("#"), _("Description"), _("Filename"), _("Size"), _("Filetype"), _("Date Uploaded"), "", "", "", "");
	
	div_start('transactions');
	start_form();
	start_table($table_style);
	table_header($th);
	$k = 0;
	while ($row = db_fetch($rows))
	{
		alt_table_row_color($k);
		
		label_cell(get_trans_view_str($type, $row['trans_no']));
		label_cell($row['description']);
		label_cell($row['filename']);
		label_cell($row['filesize']);
		label_cell($row['filetype']);
		label_cell(sql2date($row['tran_date']));
 		edit_button_cell("Edit".$row['id'], _("Edit"));
 		edit_button_cell("view".$row['id'], _("View"));
 		edit_button_cell("download".$row['id'], _("Download"));
 		edit_button_cell("Delete".$row['id'], _("Delete"));
    	end_row();
	}	
	end_table(1);
	hidden('filterType', $type);
	end_form();
	div_end();
}

//----------------------------------------------------------------------------------------

viewing_controls();

if (isset($_POST['filterType']))
	display_rows($_POST['filterType']);

start_form(true);

start_table("$table_style2 width=30%");

if ($selected_id != -1)
{
	if ($Mode == 'Edit')
	{
		$row = get_attachment($selected_id);
		$_POST['trans_no']  = $row["trans_no"];
		$_POST['description']  = $row["description"];
		hidden('trans_no', $row['trans_no']);
		label_row(_("Transaction #"), $row['trans_no']);
	}	
	hidden('selected_id', $selected_id);
}
else
	text_row_ex(_("Transaction #").':', 'trans_no', 10);
text_row_ex(_("Description").':', 'description', 40);
start_row();
label_cells(_("Attached File") . ":", "<input type='file' id='filename' name='filename'>");
end_row();

end_table(1);
if (isset($_POST['filterType']))
	hidden('filterType', $_POST['filterType']);

submit_add_or_update_center($selected_id == -1, '', true);

end_form();

end_page();

?>
