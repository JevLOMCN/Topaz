'use client'

import GeneratedXPTable from '@/components/xp/GeneratedXPTable'
import LevelCalculations from '@/components/xp/LevelCalculations'
import PercentageDifference from '@/components/xp/PercentageDifference'
import SquareAndPeak from '@/components/xp/SquareAndPeak'
import Timer from '@/components/xp/Timer'
import Vigor from '@/components/xp/Vigor'
import XPPageSkeleton from '@/components/xp/skeleton'
import type XPPerLevel from '@/data/XPPerLevel'
import { useEffect, useState } from 'react'

export default function Home() {
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    setIsLoading(false)
  }, [])

  if (isLoading) return <XPPageSkeleton />

  return (
    <>
      <Timer />

      <section className="top-30 absolute right-4 flex flex-col items-end gap-4 md:top-24">
        <SquareAndPeak />
        <Vigor />
      </section>

      <PercentageDifference />

      <LevelCalculations />

      <GeneratedXPTable />
    </>
  )
}

export type Level = keyof typeof XPPerLevel

export interface LevelState {
  initial?: Level
  initialPercentage?: string
  final?: Level
}
