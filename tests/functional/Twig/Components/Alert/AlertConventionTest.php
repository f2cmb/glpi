<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units\Twig\Components\Alert;

use Glpi\Tests\GLPITestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Fails on any alert markup that is built by hand instead of calling <twig:Alert>.
 *
 * The allowlist is what is left to convert. It only ever shrinks. Adding an entry
 * means adding duplicated markup on purpose, and needs a justification in review.
 */
class AlertConventionTest extends GLPITestCase
{
    /**
     * Directories scanned, relative to GLPI_ROOT, and the file extension read in each.
     */
    private const SCANNED = [
        'templates' => 'twig',
        'src'       => 'php',
        'front'     => 'php',
        'ajax'      => 'php',
    ];

    /**
     * The component's own templates are the one place the markup is legitimate.
     */
    private const SKIPPED_PATH = 'templates/twig_components/Alert/';

    /**
     * Hand-built alert blocks left to convert, as path => number of blocks.
     *
     * @var array<string, int>
     */
    private const ALLOWLIST = [
        'ajax/dropdownMassiveAction.php'                                                                         => 1,
        'front/updatepassword.php'                                                                               => 1,
        'src/AuthLDAP.php'                                                                                       => 2,
        'src/CommonITILValidation.php'                                                                           => 1,
        'src/DisplayPreference.php'                                                                              => 1,
        'src/Dropdown.php'                                                                                       => 1,
        'src/Glpi/Dashboard/Grid.php'                                                                            => 1,
        'src/Glpi/Error/ErrorDisplayHandler/HtmlErrorDisplayHandler.php'                                         => 1,
        'src/Glpi/Marketplace/View.php'                                                                          => 2,
        'src/Group_User.php'                                                                                     => 1,
        'src/ITILTemplateField.php'                                                                              => 1,
        'src/Lock.php'                                                                                           => 1,
        'src/NetworkName.php'                                                                                    => 1,
        'src/NetworkPortInstantiation.php'                                                                       => 3,
        'src/NotificationAjaxSetting.php'                                                                        => 1,
        'src/ProjectCost.php'                                                                                    => 1,
        'src/RSSFeed.php'                                                                                        => 1,
        'src/Reservation.php'                                                                                    => 1,
        'src/Rule.php'                                                                                           => 1,
        'src/RuleCommonITILObject.php'                                                                           => 3,
        'src/Stat.php'                                                                                           => 1,
        'templates/central/messages.html.twig'                                                                   => 3,
        'templates/components/datatable.html.twig'                                                               => 1,
        'templates/components/helpdesk_forms/delegation_alert.html.twig'                                         => 2,
        'templates/components/itilobject/actors/main.html.twig'                                                  => 1,
        'templates/components/itilobject/fields_panel.html.twig'                                                 => 1,
        'templates/components/itilobject/timeline/form_solution.html.twig'                                       => 1,
        'templates/components/itilobject/timeline/form_validation.html.twig'                                     => 2,
        'templates/components/itilobject/timeline/new_form.html.twig'                                            => 1,
        'templates/components/logs.html.twig'                                                                    => 1,
        'templates/components/messages_after_redirect_alerts.html.twig'                                          => 1,
        'templates/components/search/criteria_filter.html.twig'                                                  => 1,
        'templates/components/search/displaypreference_config.html.twig'                                         => 3,
        'templates/components/search/displaypreference_list.html.twig'                                          => 1,
        'templates/components/search/status_area.html.twig'                                                     => 1,
        'templates/error_block.html.twig'                                                                        => 1,
        'templates/install/agree_unstable.html.twig'                                                             => 1,
        'templates/install/install.install_required.html.twig'                                                  => 1,
        'templates/install/post_update_step.html.twig'                                                          => 1,
        'templates/install/step1.html.twig'                                                                     => 1,
        'templates/install/update.invalid_database.html.twig'                                                   => 1,
        'templates/install/update.need_update.html.twig'                                                        => 2,
        'templates/layout/parts/objectlock_message.html.twig'                                                   => 2,
        'templates/layout/parts/saved_searches_list.html.twig'                                                  => 1,
        'templates/layout/parts/user_header.html.twig'                                                          => 1,
        'templates/maintenance.html.twig'                                                                       => 1,
        'templates/pages/2fa/2fa_config.html.twig'                                                              => 1,
        'templates/pages/2fa/macros.html.twig'                                                                  => 1,
        'templates/pages/admin/assetdefinition/capacity/is_inventoriable_capacity_configuration_form.html.twig' => 1,
        'templates/pages/admin/entity/assistance.html.twig'                                                     => 1,
        'templates/pages/admin/entity/custom_ui.html.twig'                                                      => 1,
        'templates/pages/admin/form/access_control.html.twig'                                                   => 1,
        'templates/pages/admin/form/conditional_validation_editor.html.twig'                                    => 1,
        'templates/pages/admin/form/form_destination.html.twig'                                                 => 1,
        'templates/pages/admin/form/form_destination_form.html.twig'                                            => 1,
        'templates/pages/admin/form/form_editor.html.twig'                                                      => 1,
        'templates/pages/admin/helpdesk_home_config.html.twig'                                                  => 1,
        'templates/pages/admin/inventory/conf/config_form.html.twig'                                            => 2,
        'templates/pages/admin/inventory/upload_form.html.twig'                                                 => 1,
        'templates/pages/admin/plugins/list_suspend_banner.html.twig'                                           => 1,
        'templates/pages/admin/plugins/updatable_alert.html.twig'                                               => 1,
        'templates/pages/admin/profile/assistance.html.twig'                                                    => 1,
        'templates/pages/admin/profile/assistance_simple.html.twig'                                             => 1,
        'templates/pages/admin/rules/engine_preview_criteria.html.twig'                                         => 1,
        'templates/pages/admin/rules/engine_summary.html.twig'                                                  => 1,
        'templates/pages/admin/transfer_list.html.twig'                                                         => 2,
        'templates/pages/admin/user.substitute.html.twig'                                                       => 2,
        'templates/pages/assets/template_list.html.twig'                                                        => 1,
        'templates/pages/helpdesk/index.html.twig'                                                              => 1,
        'templates/pages/login_error.html.twig'                                                                 => 1,
        'templates/pages/setup/authentication.html.twig'                                                        => 1,
        'templates/pages/setup/authentication/other_ext_setup.html.twig'                                        => 3,
        'templates/pages/setup/crontask/crontask.html.twig'                                                     => 1,
        'templates/pages/setup/general/dbreplica_setup.html.twig'                                               => 3,
        'templates/pages/setup/general/glpinetwork_setup.html.twig'                                             => 2,
        'templates/pages/setup/general/management_setup.html.twig'                                              => 1,
        'templates/pages/setup/general/systeminfo_table.html.twig'                                              => 1,
        'templates/pages/setup/mailcollector/folder_list.html.twig'                                             => 1,
        'templates/pages/setup/mailcollector/setup_form.html.twig'                                              => 2,
        'templates/pages/setup/notification/mailing_setting.html.twig'                                          => 2,
        'templates/pages/setup/notification/translation_debug.html.twig'                                        => 1,
        'templates/pages/setup/setup_notifications.html.twig'                                                   => 2,
        'templates/pages/tools/kb/article.html.twig'                                                            => 1,
        'templates/pages/tools/savedsearch/alert_list_notification.html.twig'                                   => 1,
        'templates/pages/tools/search_knowbaseitem.html.twig'                                                   => 1,
        'templates/password_form.html.twig'                                                                     => 3,
    ];

    public function testNoHandRolledAlertMarkupOutsideTheAllowlist(): void
    {
        $found = $this->countHandRolledAlerts();

        $this->assertSame(
            self::ALLOWLIST,
            $found,
            "Hand-built alert markup does not match the allowlist.\n"
            . "Use <twig:Alert>, <twig:Alert:Info>, <twig:Alert:Warning>, <twig:Alert:Danger>\n"
            . "or <twig:Alert:Success> instead of writing class=\"alert ...\" by hand.\n"
            . "If you converted blocks, lower or remove their entry in self::ALLOWLIST."
        );
    }

    /**
     * @return array<string, int> path => number of hand-built alert blocks, sorted by path
     */
    private function countHandRolledAlerts(): array
    {
        $found = [];

        foreach (self::SCANNED as $dir => $extension) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(GLPI_ROOT . '/' . $dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== $extension) {
                    continue;
                }

                $path = str_replace(GLPI_ROOT . '/', '', $file->getPathname());

                if (str_starts_with($path, self::SKIPPED_PATH)) {
                    continue;
                }

                $count = $this->countInSource((string) file_get_contents($file->getPathname()));

                if ($count > 0) {
                    $found[$path] = $count;
                }
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * Counts class attributes carrying "alert" as a class token.
     */
    private function countInSource(string $source): int
    {
        if (preg_match_all('/class\s*=\s*(["\'])(.*?)\1/', $source, $matches) === 0) {
            return 0;
        }

        $count = 0;

        foreach ($matches[2] as $class_attribute) {
            if (in_array('alert', preg_split('/\s+/', trim($class_attribute)), true)) {
                $count++;
            }
        }

        return $count;
    }
}
