'use client'

import { SettingsAtom } from '@/atoms/Settings'
import Checkbox from '@/components/shared/Checkbox'
import Modal from '@/components/shared/Modal'
import Constitution from '@/icons/Constitution'
import EXP from '@/icons/EXP'
import Forge from '@/icons/Forge'
import * as Tabs from '@radix-ui/react-tabs'
import { useAtom } from 'jotai'
import { useTranslation } from '../../../../public/locales/client'

export default function SettingsModal() {
  const [{ displayRarity, showConstitutionPromotion, showOwnedItems }, setSettings] =
    useAtom(SettingsAtom)

  const { t } = useTranslation()

  const handleFilterChange = (option: RarityTypes) => {
    if (displayRarity.includes(option)) {
      return setSettings((prev) => ({
        ...prev,
        displayRarity: displayRarity.filter((r) => r !== option),
      }))
    }
    setSettings((prev) => ({
      ...prev,
      displayRarity: [...displayRarity, option],
    }))
  }

  return (
    <>
      <header className="flex w-full items-center justify-between">
        <Modal.Title>{t('Manage Settings')}</Modal.Title>
        <Modal.Close />
      </header>

      <form>
        <Tabs.Root defaultValue="crafting">
          <Tabs.List
            className="custom-scroll flex gap-3 overflow-auto py-2"
            aria-label="Manage your settings"
          >
            <Tabs.Trigger
              className="flex shrink-0 gap-4 rounded p-3 text-sm font-medium text-white transition-colors data-[state=active]:bg-white/10"
              value="crafting"
            >
              <Forge className="h-5 w-5 fill-white" />
              {t('Crafting Calculator')}
            </Tabs.Trigger>
            <Tabs.Trigger
              className="flex shrink-0 gap-4 rounded p-3 text-sm font-medium text-white transition-colors data-[state=active]:bg-white/10"
              value="experience"
            >
              <EXP className="h-5 w-5 fill-white" />
              {t('Experience Calculator')}
            </Tabs.Trigger>
            <Tabs.Trigger
              className="flex shrink-0 gap-4 rounded p-3 text-sm font-medium text-white transition-colors data-[state=active]:bg-white/10"
              value="constitution"
            >
              <Constitution className="h-5 w-5 fill-white" />
              {t('Constitution')}
            </Tabs.Trigger>
          </Tabs.List>

          <Tabs.Content
            className="relative flex flex-col gap-8 md:flex-row"
            value="crafting"
          >
            <div className="flex flex-1 flex-col gap-4">
              <h2>{t('Resources Filter')}</h2>

              <ul className="flex flex-col gap-2">
                {filterOptions.map((option) => (
                  <Checkbox
                    key={option}
                    label={t(`${option}`)}
                    checked={displayRarity.includes(option)}
                    onCheckedChange={() => {
                      handleFilterChange(option)
                    }}
                    disabled={option === 'Uncommon' || option === 'Common'}
                  />
                ))}
              </ul>
            </div>

            <hr className="border border-primary-400 md:h-40" />

            <div className="flex flex-1 flex-col gap-4">
              <h2>{t('Advanced View')}</h2>

              <Checkbox
                label={t('Show Owned Items')}
                checked={showOwnedItems}
                onCheckedChange={(checked) => setSettings(prev => ({ ...prev, showOwnedItems: checked as boolean }))}
              />
            </div>
          </Tabs.Content>

          <Tabs.Content
            className="relative flex flex-col gap-4"
            value="experience"
          >
            <h2>{t('Panel Visibility')}</h2>

            <ul className="flex flex-col gap-2">
              <Checkbox
                label={t('Magic Square and Secret Peak')}
                checked
                disabled
              />
              <Checkbox label={t('Vigor')} checked disabled />
            </ul>
          </Tabs.Content>

          <Tabs.Content
            className="relative flex flex-col gap-4"
            value="constitution"
          >
            <h2>{t('Additional Costs')}</h2>

            <ul className="flex flex-col gap-2">
              <Checkbox
                label={t('Show promotion cost')}
                checked={showConstitutionPromotion}
                onCheckedChange={(checked) =>
                  setSettings((prev) => ({
                    ...prev,
                    showConstitutionPromotion: checked as boolean,
                  }))
                }
              />
            </ul>
          </Tabs.Content>
        </Tabs.Root>
      </form>
    </>
  )
}

const filterOptions: RarityTypes[] = ['Epic', 'Rare', 'Uncommon', 'Common']
