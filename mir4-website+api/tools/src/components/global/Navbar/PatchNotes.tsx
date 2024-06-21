'use client'

import { SettingsAtom } from '@/atoms/Settings'
import Modal from '@/components/shared/Modal'
import PatchNotesPT from '@/content/notas-de-atualizacao.mdx'
import PatchNotesEN from '@/content/patch-notes.mdx'
// eslint-disable-next-line import/no-named-default
import { useAtomValue } from 'jotai'
import { useTranslation } from '../../../../public/locales/client'
import { cn } from '@/utils/classNames'

export default function PatchNotes({
  className,
  children,
}: {
  className?: string
  children: React.ReactNode[]
}) {
  const { t } = useTranslation()
  const settings = useAtomValue(SettingsAtom)

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
      <Modal.Content className="p-0 backdrop-blur-xl">
        <header className="flex w-full items-center justify-between border-b border-white/10 p-4">
          <Modal.Title>{t('Patch Notes')}</Modal.Title>
          <Modal.Close />
        </header>

        <section className="custom-scroll flex flex-col gap-4 overflow-auto p-4 pb-6">
          {settings.language === 'pt' ? <PatchNotesPT /> : <PatchNotesEN />}
        </section>
      </Modal.Content>
    </Modal.Wrapper>
  )
}
