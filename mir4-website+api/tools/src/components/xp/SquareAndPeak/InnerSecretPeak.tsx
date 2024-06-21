'use client'

import { XPExtension } from '@/atoms/XPCalculator'
import Input from '@/components/Input'
import { getReadableNumber, getValidNumber } from '@/utils/index'
import { useAtom } from 'jotai'
import { useTranslation } from '../../../../public/locales/client'

export default function InnerSecretPeak() {
  const { t } = useTranslation()
  const [{ secretPeak }, setExtension] = useAtom(XPExtension)

  return (
    <div className="flex flex-col gap-2 sm:flex-row sm:gap-0">
      <Input
        label={t('Tickets')}
        className="sm:w-16 text-xs text-white [&>div]:sm:rounded-r-none [&>div]:py-1"
        onChange={(e) => {
          setExtension((prev) => ({
            ...prev,
            secretPeak: {
              ...prev.secretPeak,
              tickets: getValidNumber(e.currentTarget.value, prev.secretPeak.tickets),
            },
          }))
        }}
        value={String(secretPeak.tickets)}
      />
      <Input
        label={t('XP per run')}
        className="w-full text-xs text-white [&>div]:sm:rounded-l-none [&>div]:sm:border-l-2 [&>div]:border-l-primary-500 [&>div]:py-1"
        onChange={(e) => {
          setExtension((prev) => ({
            ...prev,
            secretPeak: {
              ...prev.secretPeak,
              xpPerRun: getValidNumber(e.currentTarget.value, prev.secretPeak.xpPerRun),
            },
          }))
        }}
        value={getReadableNumber(secretPeak.xpPerRun)}
        suffix="XP"
      />
    </div>
  )
}
