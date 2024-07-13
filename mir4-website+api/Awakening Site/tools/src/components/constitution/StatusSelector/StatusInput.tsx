import Input from '@/components/Input'
import React, { useEffect, useState } from 'react'

export default function StatusInput({
  label,
  levels,
  setLevels,
}: {
  label: statusEffects
  levels: statusLevels
  setLevels: React.Dispatch<React.SetStateAction<statusLevels>>
}) {
  const [currentLevel, setCurrentLevel] = useState<{
    from: number | string
    to: number | string
  }>(levels[label])

  useEffect(() => {
    if (
      Number.isInteger(Number(currentLevel.from)) &&
      Number.isInteger(Number(currentLevel.to)) &&
      Number(currentLevel.from) > 0 &&
      Number(currentLevel.to) > 0
    ) {
      setLevels((prev) => {
        let fromValue = Number.isNaN(currentLevel.from)
          ? prev[label].from
          : currentLevel.from
        const toValue = Number.isNaN(currentLevel.to)
          ? prev[label].to
          : currentLevel.to

        if (fromValue > toValue) fromValue = toValue

        return {
          ...prev,
          [label]: {
            from: Number(fromValue),
            to: Number(toValue),
          },
        }
      })
    }
  }, [currentLevel.from, currentLevel.to])

  return (
    <div className="flex flex-col">
      <Input
        id={`from${label}`}
        className="w-24 rounded-b-none border-b border-primary-400 [&>div]:rounded-b-none"
        value={currentLevel.from ?? ''}
        onChange={(e) =>
          setCurrentLevel((prev) => ({
            ...prev,
            from: handleInput(e.target.value),
          }))
        }
        suffix="Lv."
        placeholder="45"
        autoComplete="off"
      />
      <Input
        className="w-24 rounded-t-none [&>div]:rounded-t-none"
        value={currentLevel.to ?? ''}
        onChange={(e) =>
          setCurrentLevel((prev) => ({
            ...prev,
            to: handleInput(e.target.value),
          }))
        }
        suffix="Lv."
        placeholder="50"
        autoComplete="off"
      />
    </div>
  )
}

function handleInput(value: string) {
  value = value.replace(/\D/gm, '')
  if (Number(value) > 105) value = '105'

  return value
}
