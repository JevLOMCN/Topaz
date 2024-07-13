import ConstitutionData from '@/data/ConstituionData'
import { cn } from '@/utils/classNames'
import { prepareItemForDisplay, sumObjects, toCamelCase } from '@/utils/index'
import millify from 'millify'
import Tooltip from '../../ToolTip'
import ItemFrame from '../../crafting/ItemFrame'
import React from 'react'
import { useTranslation } from '../../../../public/locales/client'

export default function StatusCostTooltip({
  isActive,
  hasLevelDifference,
  label,
  Icon,
  selectStatus,
  levels,
}: {
  isActive: boolean
  hasLevelDifference: boolean
  label: statusEffects
  Icon: (props: React.SVGProps<SVGSVGElement>) => JSX.Element
  selectStatus: () => void
  levels: statusLevels
}) {
  const { t } = useTranslation()

  return (
    <Tooltip.Wrapper open={isActive && hasLevelDifference}>
      <Tooltip.Trigger asChild>
        <button
          className={cn(
            'group z-[11] h-[4.2rem] w-[4.2rem] rounded-full bg-primary-400/10 transition-[transform,_background-color] duration-300 hover:scale-[1.2] hover:bg-primary-400/30 lg:h-[7.4rem] lg:w-[7.4rem]',
            'data-[active=true]:scale-[1.35] data-[active=true]:bg-primary-450'
          )}
          data-active={isActive}
          aria-label={t(label)}
          onClick={selectStatus}
        >
          <Icon className="h-8 w-8 fill-[#D9D5EA] transition-[filter] duration-300 group-data-[active=true]:drop-shadow-[0_1px_4px_rgb(140,140,140)] lg:h-16 lg:w-16" />
        </button>
      </Tooltip.Trigger>
      <Tooltip.Content
        className={
          'pointer-events-none z-[100] flex scale-75 items-center justify-center gap-2 overflow-auto rounded-md border-2 border-primary-450 bg-primary-600 p-2 md:scale-100'
        }
        sideOffset={16}
        side={getPopperSideByLabel(label)}
      >
        {isActive &&
          hasLevelDifference &&
          getStatusRecipeCost(levels, label).map(
            ({ name, amount, rarity }, index) => (
              <li key={index} className="flex flex-col items-center gap-2">
                <ItemFrame
                  item={toCamelCase(name) as ItemTypes}
                  rarity={rarity}
                  size="sm"
                />
                <span className="w-max rounded bg-primary-600 px-3 py-1 text-center text-sm font-medium text-white">
                  {millify(amount)}
                </span>
              </li>
            )
          )}
      </Tooltip.Content>
    </Tooltip.Wrapper>
  )
}

function getStatusRecipeCost(levels: statusLevels, label: statusEffects) {
  const result = Array(levels[label].to - levels[label].from)
    .fill(0)
    .map((_, i) => i + levels[label].from)
    .map((i) => ConstitutionData[label][i])

  const results = sumObjects(result.flat())

  if ('Level' in results) delete results.Level
  // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
  delete results[label]

  return prepareItemForDisplay(results)
}

const getPopperSideByLabel = (label: statusEffects) => {
  switch (label) {
    case 'PHYS DEF':
    case 'HP':
    case 'EVA':
      return 'right'
    case 'PHYS ATK':
      return 'top'
    default:
      return 'left'
  }
}
