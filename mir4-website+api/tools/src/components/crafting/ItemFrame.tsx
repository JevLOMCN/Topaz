import { cn } from '@/utils/classNames'
import Image from 'next/image'
import { type HTMLAttributes } from 'react'

export const rarityVariantStyles: { [key in RarityTypes | 'Default']: string } = {
  Default: 'border-[#272043] bg-default-frame',
  Legendary: 'border-[#DCC529] bg-legendary-frame',
  Epic: 'border-[#761B29] bg-epic-frame',
  Rare: 'border-[#2F60A8] bg-rare-frame',
  Uncommon: 'border-[#38896B] bg-uncommon-frame',
  Common: 'border-[#6D737A] bg-common-frame',
}

export default function ItemFrame({
  item,
  rarity,
  children,
  size = 'md',
  className,
  tier,
  quantity = 1,
  customPath,
  ...props
}: ItemFrameProps) {
  return (
    <div
      className={cn(
        'relative flex items-center justify-center rounded-lg border-2',
        rarityVariantStyles[rarity],
        { 'h-10 w-10 sm:h-14 sm:w-14': size === 'sm' },
        { 'h-14 w-14 sm:h-20 sm:w-20': size === 'md' },
        { 'h-20 w-20 sm:h-28 sm:w-28': size === 'lg' },
        className
      )}
      {...props}
    >
      <Image
        src={customPath ?? `/items/${item}.webp`}
        alt=""
        width={sizeToPX[size]}
        height={sizeToPX[size]}
        className={cn(
          'object-contain',
          { 'h-6 w-6 sm:h-9 sm:w-9': size === 'sm' },
          { 'h-9 w-9 sm:h-14 sm:w-14': size === 'md' },
          { 'h-9 w-9 sm:h-14 sm:w-14': size === 'lg' }
        )}
      />
      {tier && (
        <p className="absolute bottom-2 left-2 w-max bg-transparent bg-gradient-to-b from-[#eaecee] to-[#c1c5c7] bg-clip-text text-start font-ptSerif text-base font-bold !leading-none text-transparent drop-shadow-[0_0_1px_#000] sm:text-2xl">
          {tier === 4 ? 'IV' : 'I'.repeat(tier)}
        </p>
      )}
      {quantity > 1 && (
        <p className="absolute bottom-2 right-2 text-end text-sm font-normal leading-none text-neutral-200 drop-shadow-[0_0_2px_#000] sm:text-base">
          {quantity}
        </p>
      )}
    </div>
  )
}

const sizeToPX = {
  sm: 36,
  md: 56,
  lg: 56,
}

type ItemFrameProps = {
  item: ItemTypes | ItemCategory
  rarity: RarityTypes | 'Default'
  size?: 'sm' | 'md' | 'lg'
  tier?: ItemTier
  quantity?: number
  customPath?: string
} & HTMLAttributes<HTMLSpanElement>
