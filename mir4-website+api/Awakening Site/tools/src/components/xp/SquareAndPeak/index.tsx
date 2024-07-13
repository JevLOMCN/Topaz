'use client'

import Popover from '@/components/Popover'
import InfoTooltip from '@/components/xp/SquareAndPeak/InfoTooltip'
import InnerMagicSquare from '@/components/xp/SquareAndPeak/InnerMagicSquare'
import InnerSecretPeak from '@/components/xp/SquareAndPeak/InnerSecretPeak'
import Image from 'next/image'
import { useTranslation } from '../../../../public/locales/client'
import SquareAndPeakResult from './CalculationResult'

export default function SquareAndPeak() {
  return (
    <Popover.Wrapper>
      <Popover.Trigger
        asChild={false}
        aria-label="Open tickets menu"
        id="experienceSquareAndPeak"
        className="relative grid h-12 w-12 place-items-center rounded-full border-2 border-primary-500 bg-primary-600 transition-colors hover:border-primary-400 hover:bg-primary-500 data-[state=open]:border-primary-400 data-[state=open]:bg-primary-500"
      >
        <Image
          src="/items/peak_ticket.webp"
          alt="Secret Peak Ticket"
          width={32}
          height={32}
          className="absolute translate-x-1 rotate-12"
        />
        <Image
          src="/items/square_ticket.webp"
          alt="Magic Square Ticket"
          width={32}
          height={32}
          className="absolute -translate-x-1 -rotate-12"
        />
      </Popover.Trigger>
      <Popover.Content
        sideOffset={16}
        collisionPadding={16}
        align="end"
        className="z-[10]"
      >
        <InnerContent />
      </Popover.Content>
    </Popover.Wrapper>
  )
}

function InnerContent() {
  const { t } = useTranslation()

  return (
    <section
      className={
        'flex w-full max-w-[20rem] flex-col gap-4 rounded-lg border border-white/10 bg-primary-400/5 p-4 backdrop-blur-lg'
      }
    >
      <div className="flex items-center gap-2.5">
        <div className="flex h-12 w-12 items-center justify-center rounded-full border border-primary-500 bg-primary-600">
          <Image
            src="/items/square_ticket.webp"
            alt="Magic Square Ticket"
            width={32}
            height={32}
          />
        </div>
        <h2 className="text-xl font-bold text-white">{t('Magic Square')}</h2>
        <InfoTooltip
          content={t(
            'Enter your tickets and your XP earned per run to calculate the time taken to level up paired with peak/square runs.'
          )}
          tabIndex={-1}
        />
      </div>

      <InnerMagicSquare />

      <div className="flex items-center gap-2.5">
        <div className="flex h-12 w-12 items-center justify-center rounded-full border border-primary-500 bg-primary-600">
          <Image
            src="/items/peak_ticket.webp"
            alt="Secret Peak Ticket"
            width={32}
            height={32}
          />
        </div>
        <h2 className="text-xl font-bold text-white">{t('Secret Peak')}</h2>
      </div>

      <InnerSecretPeak />

      <SquareAndPeakResult />
    </section>
  )
}
