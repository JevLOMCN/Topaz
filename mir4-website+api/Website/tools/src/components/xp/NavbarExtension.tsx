'use client'

import { WalkthroughAtom } from '@/atoms/Walkthrough'
import { ExperienceWalkthroughStages } from '@/data/WalkthroughStages'
import QuestionMark from '@/icons/QuestionMark'
import { cn } from '@/utils/classNames'
import { retrieveWalkthroughFromStorage } from '@/utils/index'
import { useAtom } from 'jotai'
import { useEffect, useState } from 'react'
import { useTranslation } from '../../../public/locales/client'

export default function ExperienceNavExtesion() {
  const { t } = useTranslation()
  const [walk, setWalk] = useAtom(WalkthroughAtom)
  const [walkData, setWalkData] = useState({ crafting: true, xp: true })

  useEffect(() => {
    const data = retrieveWalkthroughFromStorage()
    setWalkData(data)
  }, [walk.isActive])

  return (
    <div className="flex items-center gap-4">
      <button
        onClick={() => {
          setWalk({
            stage: 0,
            isActive: true,
            stages: ExperienceWalkthroughStages(t),
            type: 'xp',
          })
        }}
        className={cn(
          'relative w-14 rounded-md p-3 transition-colors hover:bg-gray-100/10',
          {
            'before:absolute before:block before:h-8 before:w-8 before:animate-ping before:rounded-full before:bg-white/50 before:content-[""]':
              !walkData.xp,
          }
        )}
        aria-label="Walkthrough"
      >
        <div
          className={cn({
            'animate-vibrate': !walkData.xp,
          })}
        >
          <QuestionMark className={'inline-block w-6 fill-white'} />
        </div>
      </button>
    </div>
  )
}
