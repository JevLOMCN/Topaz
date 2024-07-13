import { getReadableNumber } from '@/utils/index'
import Image from 'next/image'

export default function CostFrame({ name, cost }: { name: string, cost?: number }) {
  return (
    <div className="flex items-center w-56 gap-4 rounded-full bg-primary-600 px-3 py-2 pr-6 text-xl font-bold text-white">
      <Image
        src={`/items/${name.toLocaleLowerCase()}.webp`}
        alt={`${name} icon`}
        width={32}
        height={32}
      />
      {getReadableNumber(cost ?? 0)}
    </div>
  )
}
