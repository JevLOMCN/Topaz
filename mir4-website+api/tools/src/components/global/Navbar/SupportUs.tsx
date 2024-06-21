'use client'

import Modal from '@/components/shared/Modal'
import { cn } from '@/utils/classNames'
import Image from 'next/image'
import Link from 'next/link'
import { useTranslation } from '../../../../public/locales/client'

export default function SupportUs({
  className,
  children,
}: {
  className?: string
  children: React.ReactNode[]
}) {
  const { t } = useTranslation()

  return (
    <Modal.Wrapper>
      <Modal.Trigger
        className={cn(
          'flex shrink-0 items-center justify-stretch gap-4 rounded p-3 text-base font-medium text-white transition-colors hover:bg-white/10',
          className
        )}
      >
        {children}
      </Modal.Trigger>
      <Modal.Content className="items-center gap-6 pb-6 backdrop-blur-xl">
        <header className="flex w-full items-center justify-between">
          <Modal.Title>{t('Support Us')}</Modal.Title>
          <Modal.Close />
        </header>
        <p className="text-base font-light text-white">
          {t(
            'We are a small team of players dedicated to developing high-quality tools for mir4. Even costing us money for maintenance, development, and domain retention, we make it available for free. If you like our work and can support to keep us in thriving, any amount is welcome.'
          )}
        </p>

        <Link
          href="https://www.paypal.com/donate/?hosted_button_id=RGMFCLYG4BKMY"
          className="flex w-full items-center justify-center rounded-md bg-black/10 px-4 py-3 font-medium text-white transition-colors hover:bg-black/30"
          target="_blank"
        >
          Paypal
        </Link>

        <div className="flex w-full items-center gap-3 px-4">
          <div className="h-[2px] w-full rounded-full bg-white" />
          <p className="text-lg font-bold text-white">{t('OR')}</p>
          <div className="h-[2px] w-full rounded-full bg-white" />
        </div>

        <h3 className="text-xl font-bold text-white">Binance Pay</h3>

        <p className="text-sm font-light text-white">
          Pay ID: <b className="font-medium">262205359</b>
        </p>

        <Image
          src={'/images/binance-pay.webp'}
          alt="Binance pay QR code"
          width={200}
          height={200}
        />
      </Modal.Content>
    </Modal.Wrapper>
  )
}
