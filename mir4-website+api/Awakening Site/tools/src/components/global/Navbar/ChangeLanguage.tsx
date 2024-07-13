'use client'

import { SettingsAtom } from '@/atoms/Settings'
import Translation from '@/icons/Translation'
import * as Select from '@radix-ui/react-select'
import { useAtom } from 'jotai'
import Image from 'next/image'
import { toast } from 'react-hot-toast'
import { useTranslation } from '../../../../public/locales/client'

export default function ChangeLanguage() {
  const { i18n } = useTranslation()
  const [settings, setSettings] = useAtom(SettingsAtom)

  const changeLanguage = async (val: 'en' | 'pt' | 'en') => {
    try {
      await i18n.changeLanguage(val)
      setSettings((prev) => ({ ...prev, language: val }))
      toast.success('Language updated')
    } catch (error) {
      toast.error('Something went wrong')
    }
  }

  return (
    <Select.Root
      defaultValue="en"
      value={settings.language}
      onValueChange={changeLanguage}
    >
      <Select.Trigger
        aria-label="Select Language"
        className="flex h-[3.25rem] w-[3.25rem] shrink-0 rounded-full border-2 border-transparent bg-black/20 p-2 outline-none transition-colors hover:border-white/10"
      >
        <Translation className="h-6 w-6 text-white" />
      </Select.Trigger>

      <Select.Portal>
        <Select.Content
          position="popper"
          sideOffset={12}
          align="center"
          className="relative z-[60] flex h-[--radix-select-content-trigger-height] w-max flex-col gap-2 overflow-auto rounded border border-white/10 bg-primary-400/5 p-2 font-main shadow-md backdrop-blur-lg"
        >
          <Select.Viewport>
            {languages.map(({ label, value }) => (
              <Select.Item
                className="flex cursor-pointer items-center gap-2 rounded p-2 text-base font-medium text-white outline-none transition-colors hover:bg-black/20"
                value={value}
                key={value}
              >
                <Image
                  src={`/images/${value}.png`}
                  alt={label}
                  width={20}
                  height={20}
                  className="object-contain w-5 h-5"
                />
                <Select.ItemText>{label}</Select.ItemText>
              </Select.Item>
            ))}
          </Select.Viewport>
        </Select.Content>
      </Select.Portal>
    </Select.Root>
  )
}

const languages = [
  { label: 'English', value: 'en' },
  { label: 'Português', value: 'pt' },
  { label: 'Español', value: 'es' },
]
