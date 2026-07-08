<?php

namespace SV\Pronouns;

use SV\Pronouns\Util\CustomField;
use SV\StandardLib\Helper;
use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Entity\UserField;
use function array_key_exists;

/**
 * Handles installation, upgrades, and uninstallation of the add-on.
 */
class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUninstallTrait;
    use StepRunnerUpgradeTrait;

    public function installStep1(): void
    {
        $pronounField = Helper::find(UserField::class, 'Pronoun');
        if ($pronounField == null)
        {
            $pronounField = Helper::createEntity(UserField::class);
            $pronounField->field_id = 'Pronoun';
            $pronounField->display_group = 'personal';
            $pronounField->field_type = 'textbox';
            $pronounField->moderator_editable = true;
            $pronounField->required = false;
            $pronounField->show_registration = false;
            $pronounField->user_editable = 'yes';
            $pronounField->viewable_message = false;
            $pronounField->viewable_profile = true;
            $pronounField->display_order = $this->getCustomFieldLastDisplay() + 10;
            $pronounField->match_type = 'callback';
            $pronounField->match_params = ['callback_class' => CustomField::class, 'callback_method' => 'listValidatorEn4'];

            // Need a new phrase for the title
            $title = $pronounField->getMasterPhrase(true);
            $title->phrase_text = 'Pronouns';
            $pronounField->addCascadedSave($title);

            // And another for the description
            $desc = $pronounField->getMasterPhrase(false);
            $desc->phrase_text = 'The set of pronouns with which you should be referred to. Separate each pronoun with a space. Only the English alphabetical characters are supported.';
            $pronounField->addCascadedSave($desc);

            $pronounField->save();
        }
        else
        {
            if ($pronounField->match_type === 'callback')
            {
                $matchParams =  $pronounField->match_params;
                if ($matchParams['callback_class'] === 'SV\UserEssentials\Util\CustomField')
                {
                    $matchParams['callback_class'] = CustomField::class;
                    $pronounField->match_params = $matchParams;
                }
            }
            $pronounField->saveIfChanged();
        }
    }

    public function installStep2(): void
    {
        $genderField = Helper::find(UserField::class, 'gender');
        if ($genderField === null)
        {
            return;
        }

        $choices = $genderField->field_choices;
        foreach ([
                     'genderfluid' => 'Genderfluid',
                     'nonbinary'   => 'Nonbinary',
                     'agender'     => 'Agender',
                     'other'       => 'Other',
                     'undisclosed' => 'Prefer not to say',
                 ] as $key => $value)
        {
            if (!array_key_exists($key, $choices))
            {
                $choices[$key] = $value;
            }
        }

        $genderField->field_type = 'select';
        $genderField->field_choices = $choices;
        $genderField->viewable_message = false;
        $genderField->saveIfChanged();
    }

    protected function getCustomFieldLastDisplay(): int
    {
        return (int)\XF::db()->fetchOne('select max(display_order) from xf_user_field');
    }
}