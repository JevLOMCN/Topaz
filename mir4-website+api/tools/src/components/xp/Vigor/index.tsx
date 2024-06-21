'use client'

import Popover from '@/components/Popover'
import EXP from '@/icons/EXP'
import { useTranslation } from '../../../../public/locales/client'
import InfoTooltip from '../SquareAndPeak/InfoTooltip'
import InnerVigor from './InnerVigor'

export default function Vigor() {
  const { t } = useTranslation()

  return (
    <Popover.Wrapper>
      <Popover.Trigger
        asChild={false}
        id="experienceVigor"
        aria-label="Open vigor menu"
        className="relative grid h-12 w-12 place-items-center rounded-full border-2 border-primary-500 bg-primary-600 transition-colors hover:border-primary-400 hover:bg-primary-500 data-[state=open]:border-primary-400 data-[state=open]:bg-primary-500"
      >
        <EXP className="h-6 w-6 fill-white" />
      </Popover.Trigger>
      <Popover.Content
        sideOffset={16}
        collisionPadding={16}
        align="end"
        className="z-[10]"
      >
        <section className="flex w-full max-w-[20rem] flex-col gap-4 rounded-lg border border-white/10 bg-primary-400/5 p-4 backdrop-blur-lg">
          <header className="flex justify-between">
            <h2 className="text-2xl font-bold text-white">{t('Vigor')}</h2>
            <InfoTooltip
              content={t(
                'Enter your remaining vigor in hours to calculate your XP and percentage earned during an active vigor.'
              )}
              tabIndex={-1}
            />
          </header>

          <InnerVigor />
        </section>
      </Popover.Content>
    </Popover.Wrapper>
  )
}
