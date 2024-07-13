import { getReadableNumber } from '@/utils/index'
import Tooltip from '../ToolTip'

export default function RealCostTooltip({
  cost,
  children,
}: {
  cost: number
  children: React.ReactNode
}) {
  return (
    <Tooltip.Wrapper delayDuration={0}>
      <Tooltip.Trigger>{children}</Tooltip.Trigger>
      <Tooltip.Content sideOffset={6}>{getReadableNumber(cost)}</Tooltip.Content>
    </Tooltip.Wrapper>
  )
}
