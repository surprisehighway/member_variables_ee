<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=sh_member_variables');?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	array('data' => lang('group_id'), 'style' => 'width: 5%;'),
    array('data' => lang('group'), 'style' => 'width:15%;'),
    lang('variables')
);

foreach ($settings['groups'] as $group)
{
    $this->table->add_row($group['id'], $group['name'], $group['value']);
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>