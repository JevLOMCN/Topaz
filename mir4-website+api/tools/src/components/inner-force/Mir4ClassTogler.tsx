import { SettingsAtom } from '@/atoms/Settings'
import { cn } from '@/utils/classNames'
import { useAtom } from 'jotai'
import Image from 'next/image'
import Tooltip from '../ToolTip'

export const mir4Classes = [
  'Warrior',
  'Sorcerer',
  'Taoist',
  'Arbalist',
  'Lancer',
  'Darkist',
] as const

export default function Mir4ClassToggler() {
  const [{ class: currentClass }, setSettings] = useAtom(SettingsAtom)

  return (
    <div className="flex items-center gap-1 rounded-full bg-primary-600 p-1 text-xl font-bold text-white">
      {mir4Classes.map((mir4Class) => {
        const formattedName = mir4Class.toLocaleLowerCase()

        return (
          <Tooltip.Wrapper key={formattedName}>
            <Tooltip.Trigger asChild>
              <button
                className={cn(
                  'flex h-16 w-16 flex-col gap-2 rounded-md p-1 text-sm transition-colors will-change-[colors] first:rounded-l-full first:rounded-r-md last:rounded-l-md last:rounded-r-full hover:bg-primary-500/40',
                  {
                    'bg-primary-500/40':
                      (currentClass ?? 'Arbalist') === mir4Class,
                  }
                )}
                onClick={() =>
                  setSettings((prev) => ({ ...prev, class: mir4Class }))
                }
                aria-label={`Set ${mir4Class} as your active class`}
              >
                <Image
                  src={`/images/${formattedName}.webp`}
                  alt={formattedName}
                  width={48}
                  height={48}
                  className="object-contain"
                />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Content sideOffset={12}>{mir4Class}</Tooltip.Content>
          </Tooltip.Wrapper>
        )
      })}
    </div>
  )
}
