<?php
/**
 * Flexpagemod Restore Task
 *
 * @author Mark Nielsen
 * @package format_flexpage
 */
class restore_flexpagemod_block_task extends restore_default_block_task {

    public function build() {
        if (!$this->get_setting_value('overwrite_conf') and
            ($this->get_target() == backup::TARGET_CURRENT_ADDING or
             $this->get_target() == backup::TARGET_EXISTING_ADDING)) {
            $this->built = true;
        } else {
            parent::build();
        }
    }

    /**
     * Need to remap course module IDs
     *
     * @return void
     */
    public function after_restore() {
        global $DB;

        if ($instance = $DB->get_record('block_instances', array('id' => $this->get_blockid()))) {
            $block = block_instance('flexpagemod', $instance);
            if (!empty($block->config->cmid)) {
                $info = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $block->config->cmid);

                if ($info) {
                    $cmid = $info->newitemid;
                } else {
                    $cmid = 0;
                }
                $block->instance_config_save((object) array('cmid' => $cmid));
            }
        }
    }
}