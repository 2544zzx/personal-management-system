<?php

namespace App\Controller\Page;

use App\DTO\Settings\Finances\SettingsCurrencyDTO;
use App\DTO\Settings\SettingValidationDTO;
use App\Services\Settings\SettingsLoader;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingsValidationController extends AbstractController {

    /**
     * @var SettingsLoader $settingsLoader
     */
    private $settingsLoader;

    public function __construct(
        private readonly TranslatorInterface $translator,
        SettingsLoader $settingsLoader
    ) {
        $this->settingsLoader = $settingsLoader;
    }

    /**
     * This function checks if there are no duplicated settings jsons
     * Since more that one type of dto must be validated - the second param - key will be used as getter
     * @param $dto
     * @return SettingValidationDTO
     * @throws Exception
     */
    public function isValueByKeyUnique($dto): SettingValidationDTO {
        $message = $this->translator->trans("messages.SettingValidationDTO.success");

        $settingValidationDto = new SettingValidationDTO();
        $settingValidationDto->setMessage($message);
        $settingValidationDto->setIsValid(true);

        $dtoClass = get_class($dto);

        switch( $dtoClass ){
            case SettingsCurrencyDTO::class:
                $setting      = $this->settingsLoader->getSettingsForFinances();
                $validatedKey = SettingsCurrencyDTO::KEY_NAME;

                if( empty($setting) ){
                    return $settingValidationDto;
                }

                $settingsFinancesDto = SettingsController::buildFinancesSettingsDtoFromSetting($setting);
                $savedDtosInSetting  = $settingsFinancesDto->getSettingsCurrencyDtos();

                break;
            default:
                $message = $this->translator->trans("exceptions.dtoValidation.unsupportedSetting");
                throw new Exception($message . $dtoClass);
        }

        $methodName   = "get" . $validatedKey;
        $newDtoValue = $dto->{$methodName}();

        foreach( $savedDtosInSetting as $savedDtoInSetting ){
            $savedDtoValue = $savedDtoInSetting->{$methodName}();

            if( $newDtoValue === $savedDtoValue) {
                $message = $this->translator->trans("messages.failure.SettingValidationDTO.duplicatedValue") . ": " . $validatedKey;

                $settingValidationDto->setMessage($message);
                $settingValidationDto->setIsValid(false);

                return $settingValidationDto;
            }
        }

        return $settingValidationDto;
    }

}
