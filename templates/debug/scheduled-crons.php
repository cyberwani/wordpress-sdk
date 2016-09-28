<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.7.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	$fs_options      = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );
	$plugins         = $fs_options->get_option( 'plugins' );
	$scheduled_crons = array();

	$module_types = array(
		MODULE_TYPE_PLUGIN,
		MODULE_TYPE_THEME
	);

	foreach ( $module_types as $module_type ) {
		$modules = $fs_options->get_option( $module_type . 's' );
		if ( is_array( $modules ) && count( $modules ) > 0 ) {
			foreach ( $modules as $slug => $data ) {
				if ( MODULE_TYPE_THEME === $module_type ) {
					$current_theme = wp_get_theme();
					$is_active = ( $current_theme->stylesheet === $data->file );
				} else {
					$is_active = is_plugin_active( $data->file );
				}

				/**
				 * @author Vova Feldman
				 *
				 * @since 1.2.1 Don't load data from inactive modules.
				 */
				if ( $is_active ) {
					$fs = freemius( $data->id );

					$next_execution = $fs->next_sync_cron();
					$last_execution = $fs->last_sync_cron();

					if ( false !== $next_execution ) {
						$scheduled_crons[ $slug ][] = array(
							'name' => $fs->get_plugin_name(),
							'slug' => $slug,
							'module_type' => $fs->get_module_type(),
							'type' => 'sync_cron',
							'last' => $last_execution,
							'next' => $next_execution,
						);
					}

					$next_install_execution = $fs->next_install_sync();
					$last_install_execution = $fs->last_install_sync();

					if (false !== $next_install_execution ||
						false !== $last_install_execution
					) {
						$scheduled_crons[ $slug ][] = array(
							'name' => $fs->get_plugin_name(),
							'slug' => $slug,
							'module_type' => $fs->get_module_type(),
							'type' => 'install_sync',
							'last' => $last_install_execution,
							'next' => $next_install_execution,
						);
					}
				}
			}
		}
	}
?>
<h1><?php _efs( 'scheduled-crons' ) ?></h1>
<table class="widefat">
	<thead>
	<tr>
		<th><?php _efs( 'slug' ) ?></th>
		<th><?php _efs( 'module' ) ?></th>
		<th><?php _efs( 'module-type' ) ?></th>
		<th><?php _efs( 'cron-type' ) ?></th>
		<th><?php _efs( 'Last' ) ?></th>
		<th><?php _efs( 'Next' ) ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $scheduled_crons as $slug => $crons ) : ?>
		<?php foreach ( $crons as $cron ) : ?>
			<tr>
				<td><?php echo $slug ?></td>
				<td><?php echo $cron['name'] ?></td>
				<td><?php echo $cron['module_type'] ?></td>
				<td><?php echo $cron['type'] ?></td>
				<td><?php
						if ( is_numeric( $cron['last'] ) ) {
							$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['last'] );
							$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
								$diff . ' ' . __fs( 'sec' ) :
								human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['last'] );

							if ( WP_FS__SCRIPT_START_TIME < $cron['last'] ) {
								printf( __fs( 'in-x' ), $human_diff );
							} else {
								printf( __fs( 'x-ago' ), $human_diff );
							}

//							echo ' ' . $cron['last'];
						}
					?></td>
				<td><?php
						if ( is_numeric( $cron['next'] ) ) {
							$diff       = abs( WP_FS__SCRIPT_START_TIME - $cron['next'] );
							$human_diff = ( $diff < MINUTE_IN_SECONDS ) ?
								$diff . ' ' . __fs( 'sec' ) :
								human_time_diff( WP_FS__SCRIPT_START_TIME, $cron['next'] );

							if ( WP_FS__SCRIPT_START_TIME < $cron['next'] ) {
								printf( __fs( 'in-x' ), $human_diff );
							} else {
								printf( __fs( 'x-ago' ), $human_diff );
							}
						}
					?></td>
			</tr>
		<?php endforeach ?>
	<?php endforeach ?>
	</tbody>
</table>
