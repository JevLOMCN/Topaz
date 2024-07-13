'use client'

import { WalkthroughAtom } from '@/atoms/Walkthrough'
import { useAtom } from 'jotai'
import { useLayoutEffect, useRef, useState } from 'react'
import { useTranslation } from '../../public/locales/client'
import Tooltip from './ToolTip'

export default function WalkthroughWrapper() {
  const [{ isActive, stage, stages }] = useAtom(WalkthroughAtom)
  const [elementStyles, setStylings] = useState({
    width: 0,
    height: 0,
    top: 0,
    left: 0,
  })

  const tooltipRef = useRef<HTMLButtonElement>(null)

  useLayoutEffect(() => {
    const getActiveElement = () => {
      const element = document?.querySelector(
        stages[stage ?? 0]?.id
      ) as HTMLElement
      const boundaries = element?.getClientRects()?.item(0)

      setStylings({
        width: (boundaries?.width ?? 0) + 24,
        height: (boundaries?.height ?? 0) + 24,
        top: (boundaries?.top ?? 0) + window.scrollY - 12,
        left: (boundaries?.left ?? 0) - 12,
      })
    }

    window.addEventListener('resize', getActiveElement)
    getActiveElement()

    tooltipRef?.current?.scrollIntoView({ behavior: 'smooth' })
    return () => window.removeEventListener('resize', getActiveElement)
  }, [isActive, stage])

  if (!isActive) return <></>

  return (
    <Tooltip.Wrapper open={isActive}>
      <Tooltip.Trigger>
        <button
          className="custom absolute left-64 top-64 z-[50] rounded-xl shadow-[0px_0px_0px_9999px_#00000080] duration-300"
          style={elementStyles}
          ref={tooltipRef}
        />
      </Tooltip.Trigger>
      <WalkthroughContent />
    </Tooltip.Wrapper>
  )
}

function WalkthroughContent() {
  const [{ stage, stages, type }, setWalk] = useAtom(WalkthroughAtom)
  const { t } = useTranslation()

  const isFirstStage = stage === 0
  const isLastStage = stage === stages.length - 1

  const selectedStage = stages[stage]

  const handleFinish = () => {
    const walkthroughData = JSON.parse(
      localStorage.getItem('Walkthrough') ?? '{}'
    )

    if (!type || walkthroughData[type]) return
    localStorage.setItem(
      'Walkthrough',
      JSON.stringify({ ...walkthroughData, [type]: true })
    )
  }

  const handlePrevious = () =>
    setWalk((prev) => {
      if (isFirstStage) handleFinish()

      return {
        ...prev,
        stage: isFirstStage ? 0 : prev.stage - 1,
        ...(isFirstStage ? { isActive: false } : {}),
      }
    })

  const handleNext = () =>
    setWalk((prev) => {
      if (isLastStage) handleFinish()

      return {
        ...prev,
        stage: isLastStage ? 0 : prev.stage + 1,
        ...(isLastStage ? { isActive: false } : {}),
      }
    })

  return (
    <Tooltip.Content
      className="relative z-[50] flex max-w-xs flex-col items-start gap-5 border border-primary-500 p-3"
      collisionPadding={24}
      alignOffset={24}
      sideOffset={24}
    >
      <h2 className="text-lg font-bold">{selectedStage.title}</h2>
      <p className="whitespace-pre-line text-xs font-medium">
        {selectedStage.content}
      </p>

      <footer className="flex w-full items-center gap-4">
        <button
          aria-label={isFirstStage ? t('Skip') : t('Previous')}
          className="w-full rounded-[4px] bg-[#368D6E] py-2 text-xs font-bold uppercase text-white disabled:bg-opacity-50"
          onClick={handlePrevious}
        >
          {isFirstStage ? t('Skip') : t('Previous')}
        </button>

        <button
          aria-label={isLastStage ? t('Finish') : t('Next')}
          className="w-full rounded-[4px] bg-[#473E65] py-2 text-xs font-bold uppercase text-white disabled:bg-opacity-50"
          onClick={handleNext}
        >
          {isLastStage ? t('Finish') : t('Next')}
        </button>
      </footer>
    </Tooltip.Content>
  )
}
