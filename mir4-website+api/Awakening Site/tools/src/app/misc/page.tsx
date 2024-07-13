'use client'

import Input from '@/components/Input'
import MiningNode from '@/icons/MiningNode'
import { getMiningSpeedValue } from '@/utils/index'
import { useState } from 'react'

const baseMineTime = '10.00'

export default function Home() {
  const [miningBoost, setMiningBoost] = useState('0')
  const [miningTime, setMiningTime] = useState(baseMineTime)

  const handleBoostValue = (value: string) => {
    value = value.replace(/\D/g, '')

    setMiningBoost(value)
    setMiningTime(getMiningSpeedValue(Number(value), true))
  }

  const handleMiningTime = (value: string) => {
    value = value.replace(/\D/g, '')

    if (Number(value) === 1000) value = baseMineTime
    else if (value.length > 3) return

    value = value.replace(/^(\d{1})(\d{1,2})$/, '$1.$2')

    setMiningTime(value)
    setMiningBoost(getMiningSpeedValue(Number(value), false))
  }

  return (
    <>
      <div className="flex flex-col gap-24">
        <header className="flex flex-col lg:flex-row items-center gap-4">
          <MiningNode /> <h1 className="text-2xl text-center">Mining Speed Calculator</h1>
        </header>

        <div className="flex flex-col lg:flex-row gap-8">
          <Input
            label="Mining Boost"
            suffix="%"
            value={miningBoost === 'Infinity' ? '' : miningBoost}
            onChange={(e) => handleBoostValue(e.target.value)}
          />
          <Input
            label="Time in seconds"
            suffix="s"
            min={0}
            max={10}
            value={miningTime}
            onChange={(e) => handleMiningTime(e.target.value)}
          />
        </div>
      </div>
    </>
  )
}
