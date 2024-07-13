import Discord from '@/icons/Discord'
import { cn } from '@/utils/classNames'
import { useTranslation } from '../../../../public/locales/client'

export default function DiscordGroup({
  isSidebarExpanded,
}: {
  isSidebarExpanded: boolean
}) {
  const { t } = useTranslation()
  return (
    <a
      href={'https://discord.gg/BRJxNdbJgw'}
      target="_blank"
      rel="noreferrer"
      className={cn(
        'relative flex h-10 w-full max-w-[2.5rem] shrink-0 items-center gap-4 overflow-hidden rounded p-2 transition-[color,_max-width] duration-500 hover:bg-white/10',
        { 'w-full max-w-none md:max-w-[22.5rem]': isSidebarExpanded }
      )}
    >
      <Discord className="h-6 w-6 shrink-0 fill-white" />
      <p
        className={cn(
          'overflow-hidden whitespace-nowrap text-base font-medium text-white',
          {
            'text-white': isSidebarExpanded,
          }
        )}
      >
        {t('Join our group!')}
      </p>
    </a>
  )
}
