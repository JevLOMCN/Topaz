'use client'

import { ConquestsAtom } from '@/atoms/Conquests'
import { cn } from '@/utils/classNames'
import { useAtom } from 'jotai'
import Image from 'next/image'
import { useState } from 'react'
import { useTranslation } from '../../../public/locales/client'

export default function ConquestSelection() {
  const [isLoading, setLoading] = useState(true)
  const [{ tower, stage }, setConquests] = useAtom(ConquestsAtom)
  const { t } = useTranslation()

  const handleTowerChange = (selectedTower: ConquestTowers) =>
    setConquests((prev) => ({
      ...prev,
      stage:
        selectedTower === 'Sanctuary of Hydra' && stage > 12 ? 12 : prev.stage,
      tower: selectedTower,
    }))

  return (
    <div className="custom-scroll relative mx-auto flex w-full max-w-[50rem]  shrink-0 self-start overflow-x-auto lg:max-w-[90rem]">
      <Image
        src="/conquests/main.png"
        alt=""
        width={2813}
        height={809}
        className={cn(
          'w-[50rem] min-w-[50rem] shrink-0 object-contain blur-none transition-[filter] md:rounded-lg lg:w-[90rem] lg:min-w-[90rem]',
          { 'blur-2xl': isLoading }
        )}
        placeholder="blur"
        blurDataURL="main-blur.png"
        onLoadingComplete={() => {
          setLoading(false)
        }}
      />
      {towersButtonPosition.map(({ label, styling, buttonStyling }) => (
        <label
          key={label}
          className={cn(
            'group absolute flex flex-col items-center gap-0.5 lg:gap-2',
            styling
          )}
          data-active={label === tower}
        >
          <button
            aria-label={t(label)}
            className={cn('rounded-full', buttonStyling)}
            onClick={() => handleTowerChange(label)}
          />
          <p className="max-w-[5rem] text-center justify-center flex text-sm font-extrabold text-neutral-100 drop-shadow-[0_1px_4px_rgb(0,0,0,0.6)] transition-[transform,_color,_filter] will-change-[transform,color,filter] group-hover:-translate-y-2 group-data-[active=true]:text-white group-data-[active=true]:drop-shadow-[0_1px_4px_rgb(200,200,200)] lg:max-w-[8rem] lg:text-xl">
            {t(label)}
          </p>
        </label>
      ))}
    </div>
  )
}

const towersButtonPosition = [
  {
    label: 'Mine',
    styling: ' translate-x-6 translate-y-6 lg:translate-x-12 lg:translate-y-12',
    buttonStyling: 'h-[4.75rem] w-[4.75rem] lg:h-32 lg:w-32',
  },
  {
    label: 'Forge',
    styling:
      'translate-x-[5rem] translate-y-[5.5rem] lg:translate-x-36 lg:translate-y-[9.5rem]',
    buttonStyling: 'w-[6rem] h-[4rem] lg:h-36 lg:w-36',
  },
  {
    label: 'Sanctuary of Hydra',
    styling:
      'translate-x-[10.3rem] translate-y-[5rem] lg:translate-x-[18.75rem] lg:translate-y-[9.5rem]',
    buttonStyling: 'h-[6rem] w-16 lg:h-44 lg:w-36',
  },
  {
    label: 'Tower of Conquest',
    styling:
      'translate-x-[16rem] translate-y-[1rem] lg:translate-x-[28.5rem] lg:translate-y-[1.5rem]',
    buttonStyling: 'h-24 w-20 lg:h-48 lg:w-40',
  },
  {
    label: 'Tower of Quintessence',
    styling:
      'translate-x-[20rem] translate-y-[4rem] lg:translate-x-[37rem] lg:translate-y-[7rem]',
    buttonStyling: 'h-[5.5rem] w-12 lg:h-44 lg:w-20',
  },
  {
    label: 'Millennial Tree',
    styling:
      'translate-x-[24rem] translate-y-[4.25rem] lg:translate-x-[43.5rem] lg:translate-y-[7rem]',
    buttonStyling: 'h-14 w-16 lg:h-28 lg:w-32',
  },
  {
    label: 'Portal',
    styling:
      'translate-x-[29rem] translate-y-[2rem] lg:translate-x-[52rem] lg:translate-y-[4rem]',
    buttonStyling: 'h-28 w-16 lg:h-48 lg:w-32',
  },
  {
    label: 'Tower of Victory',
    styling:
      'translate-x-[33.25rem] translate-y-[1.5rem] lg:translate-x-[60.5rem] lg:translate-y-[3rem]',
    buttonStyling: 'h-28 w-12 lg:h-48 lg:w-24',
  },
  {
    label: 'Training Sanctum',
    styling:
      'translate-x-[37.1rem] translate-y-[6.5rem] lg:translate-x-[67.5rem] lg:translate-y-[12rem]',
    buttonStyling: 'h-16 w-16 lg:h-[7rem] lg:w-28',
  },
  {
    label: 'Holy Shrine',
    styling:
      'translate-x-[42.5rem] translate-y-[0.75rem] lg:translate-x-[76rem] lg:translate-y-[1.5rem]',
    buttonStyling: 'h-28 w-20 lg:h-48 lg:w-40',
  },
] as const
