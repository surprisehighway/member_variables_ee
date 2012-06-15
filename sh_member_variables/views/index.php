<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=sh_member_variables');?>

<?php

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('group'), 'style' => 'width:15%;'),
    array('data' => lang('variable'), 'style' => 'width: 15%;'),
    array('data' => lang('value'), 'style' => 'width: 35%;'),
    lang('tag')
);

foreach ($settings['items'] as $item)
{
	$this->table->add_row(
		$item['group_id'],
		$item['name'],
		$item['value'],
		$item['tag']
	);
}

$this->table->add_row(
	$settings['new_item']['group_id'],
	$settings['new_item']['name'],
	$settings['new_item']['value'],
	$settings['new_item']['tag']
);

/*
foreach ($settings['groups'] as $group)
{
    $this->table->add_row($group['id'], $group['name'], $group['value']);
}
*/
echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>


<h2>Other Available Global Variables</h2>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading('Tag', 'Description');
$this->table->add_row('{is_site_admin}', 'Returns true if the current user has access to the ExpressionEngine control panel');
$this->table->add_row('{can_edit}', 'Returns true if the current user has access to edit channel entries');

echo $this->table->generate();

?>