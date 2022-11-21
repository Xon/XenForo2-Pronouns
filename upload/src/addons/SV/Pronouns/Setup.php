<?php

namespace SV\Pronouns;

use SV\Pronouns\Util\CustomField;
use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Entity\UserField;
use function array_key_exists;
use function assert;

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
        $this->setupCustomField();
    }

    public function installStep2(): void
    {
        $genderField = \XF::em()->find('XF:UserField', 'gender');
        if ($genderField === null)
        {
            return;
        }

        assert($genderField instanceof UserField);

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
        $genderField->saveIfChanged();
    }

    protected function setupCustomField(): void
    {
        if (!\XF::em()->find('XF:UserField', 'Pronoun'))
        {
            $customField = \XF::em()->create('XF:UserField');
            assert($customField instanceof UserField);
            $customField->field_id = 'Pronoun';
            $customField->display_group = 'personal';
            $customField->field_type = 'textbox';
            $customField->moderator_editable = true;
            $customField->required = false;
            $customField->show_registration = false;
            $customField->user_editable = 'yes';
            $customField->viewable_message = false;
            $customField->viewable_profile = true;
            $customField->display_order = $this->getCustomFieldLastDisplay() + 10;
            $customField->match_type = 'callback';
            $customField->match_params = ['callback_class' => CustomField::class, 'callback_method' => 'listValidatorEn4'];

            // Need a new phrase for the title
            $title = $customField->getMasterPhrase(true);
            $title->phrase_text = 'Pronouns';
            $customField->addCascadedSave($title);

            // And another for the description
            $desc = $customField->getMasterPhrase(false);
            $desc->phrase_text = 'The set of pronouns with which you should be referred to. Separate each pronoun with a space. Only the English alphabetical characters are supported.';
            $customField->addCascadedSave($desc);

            $customField->save();
        }
    }

    protected function getCustomFieldLastDisplay(): int
    {
        return (int)\XF::db()->fetchOne('select max(display_order) from xf_user_field');
    }
}