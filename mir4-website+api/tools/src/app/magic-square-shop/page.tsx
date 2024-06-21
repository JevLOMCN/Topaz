'use client'
import MagicSquareItemSelector from '@/components/magic-square-shop/ItemSelector'
import ShardItem from '@/components/magic-square-shop/ShardItem'

export default function MagicSquareShop() {
  return (
    <section className="mx-auto flex w-full max-w-[80rem] flex-col gap-12 overflow-x-auto px-5 !pt-40 pb-14 text-white md:p-14 md:pl-20 xl:flex-row xl:justify-center xl:gap-24">
      <div className="relative flex w-full flex-col gap-8">
        <h2 className="text-3xl font-bold">What you have</h2>

        <ul className="flex flex-row flex-wrap justify-center gap-4 xl:grid xl:grid-cols-4">
          <ShardItem item="ethereal_shard" rarity="Epic" />
          <ShardItem item="lunar_shard" rarity="Epic" />
          <ShardItem item="solar_shard" rarity="Epic" />
          <ShardItem item="boundless_shard" rarity="Epic" />

          <ShardItem item="ethereal_shard" rarity="Rare" />
          <ShardItem item="lunar_shard" rarity="Rare" />
          <ShardItem item="solar_shard" rarity="Rare" />
          <ShardItem item="boundless_shard" rarity="Rare" />

          <ShardItem item="ethereal_shard" rarity="Uncommon" />
          <ShardItem item="lunar_shard" rarity="Uncommon" />
          <ShardItem item="solar_shard" rarity="Uncommon" />
          <ShardItem item="boundless_shard" rarity="Uncommon" />
        </ul>
      </div>

      <MagicSquareItemSelector />
    </section>
  )
}
