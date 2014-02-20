<?php
$head = array('title' => 'Redact Elements');
echo head($head);
?>
<form method="post">
<section class="seven columns alpha">

<h3>Add New Elements</h3>
<p>Select an element and choose which patterns to redact from the selected element.
To add more patterns, go to the <a href="plugins/config?name=RedactElements">Redact Elements plugin configuration page</a>.</p>
<div class="field new-element">
    <div class="two columns alpha">
        <label for="">Add Element</label>
    </div>
    <div class="inputs five columns omega">
        <?php echo $this->formSelect(null, null, array(
            'multiple' => false,
            'class' => 'new-element-select'
        ), $this->select_elements) ?>
        <?php foreach ($this->settings['patterns'] as $id => $pattern): ?>
        <?php echo $this->formCheckbox(null, null, array(
            'style' => 'margin-bottom:8px;',
            'disableHidden' => true,
        ), array($id)); ?> <?php echo $pattern['label']; ?><br>
        <?php endforeach; ?>
    </div>
</div>
<button id="add-new-element">Add New Element</button>

<h3>Edit Existing Elements</h3>
<?php if (empty($this->settings['elements'])): ?>
<p>There are no redacted elements. Add an element using the above form.</p>
<?php else: ?>
<p>You can remove an existing element by unchecking all its patterns and saving changes.</p>
<?php endif; ?>

<?php foreach ($this->settings['elements'] as $elementId => $patternIds): ?>
<div class="field">
    <div class="two columns alpha">
        <label for="">Edit Element</label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <strong><?php echo $this->element_data[$elementId]['element_name']; ?></strong>
            (<?php echo $this->element_data[$elementId]['element_set_name']; ?>)
        </p>
        <?php foreach ($this->settings['patterns'] as $patternId => $pattern): ?>
        <?php echo $this->formCheckbox("elements[$elementId][]", null, array(
            'style' => 'margin-bottom:8px;',
            'disableHidden' => true,
            'checked' => in_array($patternId, $patternIds),
        ), array($patternId)); ?> <?php echo $pattern['label']; ?><br>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</section>
<section class="three columns omega">
    <div id="save" class="panel" style="margin-top: 0px;">
    <input type="submit" name="" id="" value="Save Changes" class="submit big green button"></div>
</section>
</form>
<?php echo foot(); ?>

<script>
// Update the form element names.
jQuery(document).on('click', 'select.new-element-select', function(event) {
    jQuery(this).attr('name', 'elements[' + this.value + ']');
    jQuery(this).siblings('input[type=checkbox]').attr('name', 'elements[' + this.value + '][]');
});
// Add a new pattern form block.
jQuery('#add-new-element').click(function(event) {
    event.preventDefault()
    jQuery('.new-element:first').clone().insertAfter('.new-element:last');
    jQuery('.new-element:last select').attr('name', null);
    jQuery('.new-element:last input').attr('checked', false);
});
</script>
