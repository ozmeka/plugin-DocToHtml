<div class="field">
    <div id="doc_to_html_process_label" class="two columns alpha">
        <label for="doc_to_html_process"><?php echo __('Process existing PDF files'); ?></label>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
        <?php 
        echo __(
            'This plugin extracts content from MS Word .docx files.  If the plugin is enabled, .docx files are extracted at ingestion time.  Check the box below and submit this form to process all .docx files that have been uploaded previously.');
        ?>
        </p>
        <?php if ($this->valid_storage_adapter): ?>
        <?php echo $this->formCheckbox('doc_to_html_process'); ?>
        <?php else: ?>
        <p class="error">
        <?php
        echo __(
            'This plugin does not support processing of docx files that are stored remotely. Processing existing docx files has been disabled.'
        );
        ?>
        </p>
        <?php endif; ?>
    </div>
</div>
