'use client'

import { InnerForceBloodsAtom } from '@/atoms/InnerForce'
import { cn } from '@/utils/classNames'
import { bloodNameToSet, getValidBloodValue } from '@/utils/index'
import { useAtom } from 'jotai'
import Image from 'next/image'

export default function BloodFrame({
  bloodName,
  Icon,
}: {
  bloodName: BloodNames
  Icon: (props: React.SVGProps<SVGSVGElement>) => JSX.Element
}) {
  const [bloodValues, setBloodValues] = useAtom(InnerForceBloodsAtom)

  const selectAll = (e: React.MouseEvent<HTMLInputElement, MouseEvent>) =>
    e.currentTarget.setSelectionRange(0, 25, 'backward')

  const handleValueChange = (
    e: React.ChangeEvent<HTMLInputElement>,
    type: 'initial' | 'final'
  ) =>
    setBloodValues((prev) => ({
      ...prev,
      [bloodName]: {
        ...prev[bloodName],
        [type]: getValidBloodValue(
          bloodNameToSet[bloodName],
          Number(e.currentTarget.value.replace(/\D/g, ''))
        ),
      },
    }))

  return (
    <li>
      <label className="group flex h-32 w-32 flex-col items-center justify-center transition-transform will-change-transform">
        <Image
          src="/images/skill-frame.svg"
          alt=""
          width={128}
          height={132}
          className="select-none object-contain "
        />
        <Icon className="absolute h-14 w-14 -translate-y-1" />
        <div className="absolute flex translate-x-[0.04rem] translate-y-[2.75rem] items-center gap-[0.85rem] text-xs font-bold text-white">
          <input
            className={cn(
              'h-10 w-10 shrink-0 bg-transparent text-center focus:outline-none',
              {
                'text-csred-400 selection:bg-csred-400/20':
                  bloodValues[bloodName].initial > bloodValues[bloodName].final,
              }
            )}
            defaultValue={1}
            value={bloodValues[bloodName].initial}
            onClick={selectAll}
            onChange={(e) => handleValueChange(e, 'initial')}
          />
          <input
            className={cn(
              'h-10 w-10 shrink-0 bg-transparent text-center focus:outline-none',
              {
                'text-csred-400 selection:bg-csred-400/20':
                  bloodValues[bloodName].final < bloodValues[bloodName].initial,
              }
            )}
            defaultValue={1}
            value={bloodValues[bloodName].final}
            onClick={selectAll}
            onChange={(e) => handleValueChange(e, 'final')}
          />
        </div>
      </label>
    </li>
  )
}
