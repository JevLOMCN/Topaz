'use client'

import { type Level } from '@/app/xp/page'
import { XPCalculatorAtom, XPInvalidInput } from '@/atoms/XPCalculator'
import XPPerLevel from '@/data/XPPerLevel'
import { getPercentage, getReadableNumber } from '@/utils/index'
import {
  flexRender,
  getCoreRowModel,
  useReactTable,
  type ColumnDef,
} from '@tanstack/react-table'
import humanizeDuration from 'humanize-duration'
import { useAtomValue } from 'jotai'
import millify from 'millify'
import moment from 'moment'
import { useMemo } from 'react'
import { useTranslation } from '../../../public/locales/client'

export default function GeneratedXPTable() {
  const {
    levels,
    xpPerMinute = 0,
    manualCalculation,
    percentages,
  } = useAtomValue(XPCalculatorAtom)
  const invalidInput = useAtomValue(XPInvalidInput)
  const { t, i18n } = useTranslation()

  const LevelGap =
    levels.initial && levels.final
      ? XPPerLevel[(Number(levels.final) - 1) as Level]
      : ''

  const XPToTargetLevel = LevelGap
    ? getPercentage(
        LevelGap,
        100 - Number(levels.initialPercentage ?? percentages.final)
      )
    : undefined

  const XPPerMinute = xpPerMinute || (manualCalculation.xpPerMinute ?? 0)

  const currentLvl = levels.initial

  const data = useMemo(
    () =>
      currentLvl
        ? generateTableData(XPPerMinute, XPToTargetLevel ?? 0, currentLvl)
        : [],
    [XPPerMinute, XPToTargetLevel, currentLvl]
  )

  const columns = useMemo(() => getColumns(t, i18n.language), [])

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    state: {
      columnVisibility: { currentPercentage: false },
    },
  })

  if (!XPPerMinute || invalidInput) return <></>

  return (
    <section className="relative mt-12 flex flex-col rounded-md bg-primary-600 p-1 md:mt-16 md:rounded-xl">
      <table className="relative w-full">
        <thead className="border-b-2 border-primary-500/50">
          {table.getHeaderGroups().map((headerGroup) => (
            <tr key={headerGroup.id}>
              {headerGroup.headers.map((header) => (
                <th
                  key={header.id}
                  className="px-3 py-1.5 text-xs font-bold text-white md:px-6 md:py-3 md:text-xl"
                >
                  {flexRender(
                    header.column.columnDef.header,
                    header.getContext()
                  )}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.map((row) => (
            <tr
              key={row.id}
              className="[&:nth-child(even)>*]:bg-primary-500/20"
            >
              {row.getVisibleCells().map((cell) => (
                <td
                  key={cell.id}
                  className="px-3 py-1.5 text-xs font-light text-white first:rounded-l-md last:rounded-r-md md:px-6 md:py-3 md:text-sm"
                >
                  {flexRender(cell.column.columnDef.cell, cell.getContext())}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  )
}

interface TableXP {
  levelReached: Level
  currentPercentage: number
  XPEarned: number
  timeInMinutes: number
}

const generateTableData = (
  XPPerMinute: number,
  XPToTargetLevel: number,
  currentLvl: Level
): TableXP[] => {
  const timeSets = [30, 60, 240, 480, 720]
  const result: TableXP[] = []

  timeSets.forEach((timeInMinutes) => {
    const XPEarned = timeInMinutes * XPPerMinute

    if (XPToTargetLevel - XPEarned < 0) return

    const percentageOfCurrent = (XPEarned / XPPerLevel[currentLvl]) * 100

    return result.push({
      levelReached: currentLvl,
      currentPercentage: percentageOfCurrent,
      timeInMinutes,
      XPEarned,
    })
  })

  return [
    ...result,
    {
      levelReached: (Number(currentLvl) + 1) as Level,
      currentPercentage: 0,
      timeInMinutes: XPToTargetLevel / XPPerMinute,
      XPEarned: XPToTargetLevel,
    },
  ]
}

const getColumns = (
  t: (key: string) => string,
  language: string
): Array<ColumnDef<TableXP>> => [
  {
    accessorKey: 'levelReached',
    header: () => t('Progression'),
    cell: ({ getValue, row }) => {
      const percentage = row.getValue('currentPercentage')

      if (typeof percentage !== 'number') return
      return (
        <span className="font-light">
          <b className="font-bold">{String(getValue())}</b>{' '}
          {percentage ? `(+${percentage.toFixed(2)}%)` : ''}
        </span>
      )
    },
    enableSorting: false,
  },
  {
    accessorKey: 'currentPercentage',
    enableHiding: true,
  },
  {
    accessorKey: 'XPEarned',
    header: () => (
      <span className="flex md:min-w-[15rem]">{t('XP Earned')}</span>
    ),
    cell: ({ getValue }) => (
      <>
        <b className="font-extrabold">{`${millify(getValue() as number)} -`}</b>{' '}
        {getReadableNumber(Math.round(getValue() as number))}
      </>
    ),
    enableSorting: false,
  },
  {
    accessorKey: 'timeInMinutes',
    header: () => <span className="flex w-full justify-end">{t('Time')}</span>,
    cell: ({ getValue }) => (
      <span className="flex w-full justify-end text-end">
        {humanizeDuration(
          moment
            .duration(getValue() as moment.DurationInputArg1, 'minutes')
            .asMilliseconds(),
          { round: true, language }
        )}
      </span>
    ),
    enableSorting: false,
  },
]
