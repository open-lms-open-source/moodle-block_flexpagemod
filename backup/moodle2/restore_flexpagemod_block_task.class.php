<?php
/**
 * Flexpagemod Restore Task
 */
class restore_flexpagemod_block_task extends restore_default_block_task {
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