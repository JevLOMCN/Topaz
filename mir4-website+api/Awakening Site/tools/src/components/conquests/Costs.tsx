'use client'

import { ConquestsAtom } from '@/atoms/Conquests'
import ConquestTowersData from '@/data/ConquestTowerData'
import { getReadableNumber } from '@/utils/index'
import { useAtomValue } from 'jotai'
import Image from 'next/image'

export default function ConquestCosts() {
  const { tower, stage } = useAtomValue(ConquestsAtom)

  const currentTower = ConquestTowersData[tower].Steps[stage]

  return (
    <footer className="mt-10 flex flex-wrap justify-center flex-row items-center gap-4">
      {Object.entries(currentTower.Cost).map(([key, value]) => (
        <div
          key={key}
          className="flex items-center gap-4 rounded-full bg-primary-600 px-3 py-2 pr-6 text-xl font-bold text-white"
        >
          <Image
            src={`/items/${key.toLowerCase()}.webp`}
            alt={key}
            width={32}
            height={32}
          />
          {getReadableNumber(value)}
        </div>
      ))}
    </footer>
  )
}
