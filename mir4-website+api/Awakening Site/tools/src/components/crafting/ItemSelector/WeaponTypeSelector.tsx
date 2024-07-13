import { ItemSelectorAtom } from '@/atoms/CraftingCalc'
import MenuButton from '@/components/crafting/ItemSelector/MenuButton'
import { useAtom } from 'jotai'
import { useTranslation } from '../../../../public/locales/client'

export default function WeaponTypeSelector() {
  const { t } = useTranslation()
  const [{ category, weaponType }, setItemToCraft] = useAtom(ItemSelectorAtom)

  return (
    <>
      {category === 'weapon' ? (
        <div className="flex gap-2.5 text-sm font-medium text-white">
          <MenuButton
            onClick={() =>
              setItemToCraft((prev) => ({
                ...prev,
                weaponType: 'primary',
              }))
            }
            className={'py-2 data-[active=true]:bg-primary-100/10'}
            data-active={weaponType === 'primary'}
          >
            {t('Primary')}
          </MenuButton>
          <MenuButton
            onClick={() =>
              setItemToCraft((prev) => ({
                ...prev,
                weaponType: 'secondary',
              }))
            }
            className={'py-2 data-[active=true]:bg-primary-100/10'}
            data-active={weaponType === 'secondary'}
          >
            {t('Secondary')}
          </MenuButton>
        </div>
      ) : (
        <></>
      )}
    </>
  )
}
