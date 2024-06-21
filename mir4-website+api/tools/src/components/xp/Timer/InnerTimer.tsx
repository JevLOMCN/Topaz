'use client'

import { customTimerAtom } from '@/atoms/XPCalculator'
import Input from '@/components/Input'
import { cn } from '@/utils/classNames'
import { useAtom } from 'jotai'
import moment from 'moment'
import { useRef, useState } from 'react'
import useSound from 'use-sound'
import { useTranslation } from '../../../../public/locales/client'

export default function InnerTimer() {
  const [play] = useSound('/audio/timer.mp3', { volume: 0.2 })
  const [customTimer, setCustomTimer] = useAtom(customTimerAtom)
  const [timerState, setTimerState] = useState<TimerState>({
    start: undefined,
    now: moment.now(),
    isActive: false,
  })
  const countRef = useRef<any>(null)

  const { t } = useTranslation()

  const handleStart = () => {
    if (
      Number.isNaN(Number(customTimer.minutes)) ||
      Number.isNaN(Number(customTimer.seconds))
    ) {
      return
    }

    setTimerState((prev) => ({ ...prev, isActive: true, start: moment.now() }))

    countRef.current = setInterval(() => {
      setTimerState((prev) => {
        const targetTime = moment
          .duration(moment.now())
          .subtract({
            minutes: Number(customTimer.minutes),
            seconds: Number(customTimer.seconds),
          })
          .as('milliseconds')
        const timeDifference = moment(prev.start).diff(targetTime)

        if (timeDifference <= 0) {
          play()
          handleReset()
        }

        return { ...prev, now: moment.now() }
      })
    }, 1000)
  }

  const handleReset = () => {
    clearInterval(countRef.current)
    setTimerState((prev) => ({ ...prev, isActive: false, start: undefined }))
  }

  const differenceDate = Math.round(
    moment
      .duration(moment(timerState.start).diff(moment.now()))
      .add({
        minutes: Number(customTimer.minutes),
        seconds: Number(customTimer.seconds),
      })
      .asSeconds()
  )

  const minutes = Math.floor(differenceDate / 60)
  const seconds = Number(differenceDate % 60).toLocaleString('en', {
    minimumIntegerDigits: 2,
  })

  const showStopwatch = !!timerState.start && differenceDate > 0
  return (
    <>
      <title>
        {showStopwatch
          ? `[${minutes}:${seconds}] Timer`
          : 'Experience Calculator'}
      </title>

      <div
        className={cn(
          'flex flex-row items-center gap-2 text-5xl font-bold text-white sm:text-6xl transition-transform duration-300',
          { '-translate-y-4': timerState.isActive }
        )}
      >
        <Input
          placeholder="05"
          label={t('Minutes')}
          value={timerState.isActive ? minutes : customTimer.minutes}
          onChange={(e) =>
            setCustomTimer((prev) => ({
              ...prev,
              minutes: formatTimerValue(e.target.value),
            }))
          }
          disabled={timerState.isActive}
          error={Number.isNaN(Number(customTimer.minutes))}
          className={cn(
            'transition-colors duration-300 [&>div>input]:w-24 [&>div>input]:text-5xl [&>div>input]:font-bold [&>div>input]:sm:text-6xl [&>div]:transition-[transform,_background-color] [&>div]:duration-300',
            {
              'text-transparent [&>div]:-translate-y-2 [&>div]:bg-transparent': timerState.isActive,
            }
          )}
        />
        :
        <Input
          placeholder="05"
          label={t('Seconds')}
          value={timerState.isActive ? seconds : customTimer.seconds}
          onChange={(e) =>
            setCustomTimer((prev) => ({
              ...prev,
              seconds: formatTimerValue(e.target.value),
            }))
          }
          disabled={timerState.isActive}
          error={Number.isNaN(Number(customTimer.seconds))}
          className={cn(
            'transition-colors duration-300 [&>div>input]:w-24 [&>div>input]:text-5xl [&>div>input]:font-bold [&>div>input]:sm:text-6xl [&>div]:transition-[transform,_background-color] [&>div]:duration-300',
            {
              'text-transparent [&>div]:-translate-y-2 [&>div]:bg-transparent': timerState.isActive,
            }
          )}
        />
      </div>

      <div className="flex flex-row gap-3">
        <button
          aria-label="Start timer"
          onClick={handleStart}
          className="rounded-[4px] bg-[#368D6E] px-4 py-2 text-xs font-bold uppercase text-white disabled:bg-opacity-50"
          disabled={timerState.isActive}
        >
          {t('Start')}
        </button>

        <button
          aria-label="Reset timer"
          onClick={handleReset}
          className="rounded-[4px] bg-[#473E65] px-4 py-2 text-xs font-bold uppercase text-white disabled:bg-opacity-50"
          disabled={!timerState.isActive}
        >
          {t('Reset')}
        </button>
      </div>
    </>
  )
}

function formatTimerValue(timer: string) {
  timer = timer.replace(/\D/g, '')
  if (Number(timer) > 60) return '60'

  return timer
}

interface TimerState {
  start?: number
  now?: number
  isActive: boolean
}
