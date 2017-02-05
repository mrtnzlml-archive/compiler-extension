<?php declare(strict_types = 1);

namespace Adeira;

final class GroupedNeonAdapter extends \Nette\DI\Config\Adapters\NeonAdapter
{

	public function process(array $arr)
	{
		foreach ($arr as &$configKeys) {
			if (is_array($configKeys)) {
				foreach ($configKeys as $originalKey => $entity) {
					if ($entity instanceof \Nette\Neon\Entity) {
						if (\Nette\Utils\Strings::endsWith($entity->value, '\\')) {
							if (!$this->isEntityRegisteredAsAnonymous($originalKey)) {
								$message = "Service with grouped classes must be anonymous. You have to remove key '$originalKey' to use this feature.";
								throw new \Nette\Neon\Exception($message);
							}

							unset($configKeys[$originalKey]);

							foreach ($entity->attributes as $attributeKey => $attribute) {
								if (!$this->isEntityRegisteredAsAnonymous($attributeKey)) {
									$message = "Grouped classes in service definition must be anonymous. Please remove key '$attributeKey'.";
									throw new \Nette\Neon\Exception($message);
								}

								$configKeys[] = $entity->value . $attribute; //add grouped services
							}
						}
					}
				}
			}
		}
		unset($configKeys); //unreference
		return parent::process($arr);
	}

	private function isEntityRegisteredAsAnonymous($entityKey)
	{
		return (string)(int)$entityKey === (string)$entityKey; //anonymous
	}

}
