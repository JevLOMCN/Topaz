'use client'
import { InnerForceBloodsAtom, InnerForceTabAtom } from '@/atoms/InnerForce'
import { SettingsAtom } from '@/atoms/Settings'
import { ItemRarities } from '@/components/constitution/CostInformation'
import BloodFrame from '@/components/inner-force/BloodFrame'
import CostFrameSkeleton from '@/components/inner-force/CostFrame/Skeleton'
import DesktopEffectsTableSkeleton from '@/components/inner-force/EffectsTable/Desktop.Skeleton'
import MobileEffectsTableSkeleton from '@/components/inner-force/EffectsTable/Mobile.skeleton'
import Mir4ClassToggler from '@/components/inner-force/Mir4ClassTogler'
import TabButtonSkeleton from '@/components/inner-force/TabButton.skeleton'
import TierHandlerSkeleton from '@/components/inner-force/TierHandler.skeleton'
import Checkbox from '@/components/shared/Checkbox'
import {
  calculateBloodCost,
  calculateBloodEffects,
  calculateUpgradeCost,
  extractItemRarity,
  formatItemName,
  getBloodIcon,
  getBloodSetObject,
  getBloodsByTab,
} from '@/utils/index'
import { useAtom, useAtomValue } from 'jotai'
import dynamic from 'next/dynamic'
import { useMemo } from 'react'
import { useTranslation } from '../../../public/locales/client'

const TabButton = dynamic(
  async () => await import('@/components/inner-force/TabButton'),
  {
    ssr: false,
    loading: () => <TabButtonSkeleton />,
  }
)
const CostFrame = dynamic(
  async () => await import('@/components/inner-force/CostFrame'),
  {
    ssr: false,
    loading: () => <CostFrameSkeleton />,
  }
)
const TierHandler = dynamic(
  async () => await import('@/components/inner-force/TierHandler'),
  {
    ssr: false,
    loading: () => <TierHandlerSkeleton />,
  }
)
const ItemCostList = dynamic(
  async () => await import('@/components/inner-force/ItemCostList'),
  {
    ssr: false,
  }
)
const DesktopEffectsTable = dynamic(
  async () => await import('@/components/inner-force/EffectsTable/Desktop'),
  {
    ssr: false,
    loading: () => <DesktopEffectsTableSkeleton />,
  }
)
const MobileEffectsTable = dynamic(
  async () => await import('@/components/inner-force/EffectsTable/Mobile'),
  {
    ssr: false,
    loading: () => <MobileEffectsTableSkeleton />,
  }
)

export default function InnerForce() {
  const bloodTab = useAtomValue(InnerForceTabAtom)
  const bloodObject = useAtomValue(InnerForceBloodsAtom)
  const [{ class: mir4Class, showInnerForcePromotion }, setSettings] =
    useAtom(SettingsAtom)
  const { t } = useTranslation()

  const sortedResult = useMemo(() => {
    const object = calculateBloodCost(getBloodSetObject(bloodTab, bloodObject), mir4Class)

    const sortedObject = Object.entries(object)
      .sort(([name1], [name2]) => {
        const formatted1 = formatItemName(name1)
        const formatted2 = formatItemName(name2)

        if (formatted1 > formatted2) return -1
        if (formatted1 < formatted2) return 1

        return 0
      })
      .sort(([name1], [name2]) => {
        const rarity1 = ItemRarities.indexOf(extractItemRarity(name1))
        const rarity2 = ItemRarities.indexOf(extractItemRarity(name2))

        if (rarity1 > rarity2) return -1
        if (rarity1 < rarity2) return 1

        return 0
      })

    return sortedObject
  }, [JSON.stringify(bloodObject), mir4Class, bloodTab])

  const upgradeResult = useMemo(() => {
    if (!showInnerForcePromotion) return []
    const upgradeObject = calculateUpgradeCost(
      getBloodSetObject(bloodTab, bloodObject),
      mir4Class || 'Arbalist'
    )

    const sortedObject = Object.entries(upgradeObject)
      .sort(([name1], [name2]) => {
        const formatted1 = formatItemName(name1)
        const formatted2 = formatItemName(name2)

        if (formatted1 > formatted2) return -1
        if (formatted1 < formatted2) return 1

        return 0
      })
      .sort(([name1], [name2]) => {
        const rarity1 = ItemRarities.indexOf(extractItemRarity(name1))
        const rarity2 = ItemRarities.indexOf(extractItemRarity(name2))

        if (rarity1 > rarity2) return -1
        if (rarity1 < rarity2) return 1

        return 0
      })

    return sortedObject
  }, [JSON.stringify(bloodObject), mir4Class, showInnerForcePromotion, bloodTab])

  const effectsObject = useMemo(() => {
    const object = calculateBloodEffects(
      getBloodSetObject(bloodTab, bloodObject),
      mir4Class,
      showInnerForcePromotion
    )
    const formattedObject = Object.entries(object).sort(([name1], [name2]) => {
      if (name1 < name2) return -1
      if (name1 > name2) return 1

      return 0
    })

    return formattedObject
  }, [
    JSON.stringify(bloodObject),
    mir4Class,
    showInnerForcePromotion,
    bloodTab,
  ])

  return (
    <div className="relative py-12 mx-auto flex w-full flex-col items-center justify-center gap-8 md:pl-[5.25rem] px-6 pt-40 md:pt-24 selection:bg-primary-800 xl:flex-row xl:items-start">
      <aside className="custom-scroll mx-auto flex w-full max-w-max flex-row gap-4 overflow-x-auto py-4 xl:mx-0 xl:w-max xl:shrink-0 xl:flex-col xl:py-0">
        <TabButton tabName="Muscle Strength Manual" />
        <TabButton tabName="Nine Yin Manual" />
        <TabButton tabName="Nine Yang Manual" />
        <TabButton tabName="Violet Mist Art" />
        <TabButton tabName="Northern Profound Art" />
        <TabButton tabName="Toad Stance" />
      </aside>

      <div className="flex max-w-[40rem] flex-col items-center gap-8">
        <Mir4ClassToggler />

        <ol className="grid scale-[0.8] grid-cols-2 items-center gap-6 sm:scale-100 md:flex">
          {getBloodsByTab[bloodTab].map((blood) => (
            <BloodFrame
              key={blood}
              bloodName={blood}
              Icon={getBloodIcon[blood]}
            />
          ))}
        </ol>

        <div className="flex flex-col items-center gap-4 sm:flex-row">
          <TierHandler />

          <CostFrame
            name="Energy"
            cost={sortedResult.find(([name]) => name === 'energy')?.[1]}
          />

          <CostFrame
            name="Copper"
            cost={upgradeResult.find(([name]) => name === 'copper')?.[1]}
          />
        </div>

        <Checkbox
          label={t('Show promotion cost')}
          onCheckedChange={(checked) =>
            setSettings((prev) => ({
              ...prev,
              showInnerForcePromotion: checked as boolean,
            }))
          }
          checked={showInnerForcePromotion}
        />

        <div className="flex flex-col gap-6">
          <ItemCostList sortedResult={sortedResult} />

          {showInnerForcePromotion && upgradeResult.length > 1 ? (
            <>
              <h2 className="mt-4 text-xl font-bold md:text-2xl">
                {t('Promotion Cost')}
              </h2>

              <ItemCostList sortedResult={upgradeResult} />
            </>
          ) : null}
        </div>
      </div>

      <MobileEffectsTable effectsObject={effectsObject} />

      <DesktopEffectsTable effectsObject={effectsObject} />
    </div>
  )
}
