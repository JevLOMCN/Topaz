'use client'
import { statusLevelsAtom } from '@/atoms/Constitution'
import { useAtom } from 'jotai'

export default function TierHandler() {
  const [levels, setLevels] = useAtom(statusLevelsAtom)

  const handleStageChange = (type: 'increment' | 'decrement') => {
    const minLevel = Math.min(
      ...Object.values(levels).map((values) => values.from)
    )
    const tier = Math.round(minLevel / 5)

    const newValues = Object.values(levels).map(({ from, to }) => ({
      from: getValidNumber(5 * (type === 'increment' ? tier + 1 : tier - 1)),
      to: getValidNumber(5 * (type === 'increment' ? tier + 2 : tier)),
    }))

    const merged = Object.keys(levels).reduce(
      (obj, key, index) => ({ ...obj, [key]: newValues[index] }),
      {}
    ) as {
      [key in statusEffects]: { from: number; to: number }
    }

    setLevels(merged)
  }

  const minLevel = Math.min(
    ...Object.values(levels).map((values) => values.from)
  )
  const currentTier = Math.round(minLevel / 5) + 1

  return (
    <div className="absolute flex items-center text-base font-bold text-white">
      <button
        aria-label={'Decrement constitution tier'}
        onClick={() => handleStageChange('decrement')}
        className="rounded-l-full bg-primary-500 p-2 transition-colors hover:bg-primary-450"
      >
        -
      </button>
      <p className="bg-primary-500 p-2">Tier {currentTier}</p>
      <button
        aria-label={'Increment constitution tier'}
        onClick={() => handleStageChange('increment')}
        className="rounded-r-full bg-primary-500 p-2 transition-colors hover:bg-primary-450"
      >
        +
      </button>
    </div>
  )
}

function getValidNumber(value: number) {
  if (value < 1) return 1
  if (value > 105) return 105
  return value
}
