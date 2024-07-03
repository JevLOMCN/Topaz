import {
  AllowedInventoryItemTypes,
  effectToBloodName,
  formatEffectValue,
  formatItemName,
  getBloodIcon,
} from '@/utils/index'
import { useTranslation } from '../../../../public/locales/client'

export default function DesktopEffectsTable({
  effectsObject,
}: {
  effectsObject: Array<
    [
      string,
      {
        initial: number
        final: number
      }
    ]
  >
}) {
  const { t } = useTranslation()

  return (
    <div className="relative flex-col hidden max-h-[calc(100vh-10rem)] w-[23rem] rounded-md bg-primary-600 p-1 md:rounded-xl xl:flex">
      <h2 className="w-full px-4 py-2 text-center">{t('Character Status')}</h2>
      <ul className="custom-scroll flex flex-col gap-1 overflow-auto">
        {effectsObject.map(([name, value]) => {
          const formattedName = formatItemName(name)

          if (
            AllowedInventoryItemTypes.includes(formattedName) ||
            value.final < value.initial
          ) {
            return <></>
          }

          const Icon = getBloodIcon[effectToBloodName[name as statusEffects]]

          const hasIncreased = value.final > value.initial

          return (
            <li
              key={name}
              className="flex items-center gap-4 rounded bg-primary-500/20 px-1 py-0.5 text-xs font-light text-white md:px-3 md:py-1.5 md:text-sm"
            >
              <Icon className="h-6 w-6 shrink-0" />{' '}
              <b className="mr-auto font-bold">{t(name)}</b>
              <p className="ml-4 shrink-0 text-end font-medium">
                {hasIncreased ? (
                  <>
                    {formatEffectValue(name, value.initial)}{' '}
                    <span className="text-success-400">
                      {'>'} {formatEffectValue(name, value.final)}
                    </span>
                  </>
                ) : (
                  formatEffectValue(name, value.initial)
                )}
              </p>
            </li>
          )
        })}
      </ul>
    </div>
  )
}
