<p>To choose which elements to redact, go to the <a href="<?php echo url('redact-elements'); ?>">Redact Elements administrative page</a>.</p>
<div class="field">
    <div class="two columns alpha">
        <label for="override[]">Role Overrides</label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">Override redactions for the following user roles:</p>
        <?php echo $view->formCheckbox('overrides[]', 'super', array(
            'checked' => in_array('super', $settings['overrides']),
        )); ?> Super<br>
        <?php echo $view->formCheckbox('overrides[]', 'admin', array(
            'checked' => in_array('admin', $settings['overrides']),
        )); ?> Admin<br>
        <?php echo $view->formCheckbox('overrides[]', 'researcher', array(
            'checked' => in_array('researcher', $settings['overrides']),
        )); ?> Researcher<br>
        <?php echo $view->formCheckbox('overrides[]', 'contributor', array(
            'checked' => in_array('contributor', $settings['overrides']),
        )); ?> Contributor
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="replacement">Replacement Text</label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">Replace redacted text with the following text:</p>
        <?php echo $view->formText('replacement', $settings['replacement']) ?>
    </div>
</div>

<h3>Add New Patterns</h3>
<p>Patterns are labeled regular expressions that identify what sequence of
characters should be redacted. For information on regular expressions, see
<a href="http://www.regular-expressions.info/" target="_blank">Regular-Expressions.info</a>.</p>
<div class="field new-pattern">
    <div class="two columns alpha">
        <label for="labels[]">Add Pattern</label>
    </div>
    <div class="inputs five columns omega">
        <?php echo $view->formText("new-labels[]", null, array(
            'placeholder' => 'Enter a label'
        )) ?>
        <?php echo $view->formTextarea("new-regexs[]", null, array(
            'rows' => 6,
            'placeholder' => 'Enter a regular expression',
        )) ?>
    </div>
</div>
<button id="add-new-pattern">Add New Pattern</button>

<h3>Edit Existing Patterns</h3>
<?php if (empty($settings['patterns'])): ?>
<p>There are no existing patterns. Add a pattern using the above form.</p>
<?php else: ?>
<p>You can delete an existing pattern by removing its regular expression and
saving changes.</p>
<?php endif; ?>
<?php foreach ($settings['patterns'] as $key => $pattern): ?>
<div class="field">
    <div class="two columns alpha">
        <label for="labels[]">Edit Pattern</label>
    </div>
    <div class="inputs five columns omega">
        <?php echo $view->formText("labels[$key]", $pattern['label'], array(
            'placeholder' => 'Enter a label'
        )) ?>
        <?php echo $view->formTextarea("regexs[$key]", $pattern['regex'], array(
            'rows' => 6,
            'placeholder' => 'Enter a regular expression',
        )) ?>
    </div>
</div>
<?php endforeach; ?>

<script>
// Add a new pattern form block.
jQuery('#add-new-pattern').click(function(event) {
    event.preventDefault()
    jQuery('.new-pattern:first').clone().insertAfter('.new-pattern:last');
    jQuery('.new-pattern:last input').val('');
});
</script>

