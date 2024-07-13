import { InventoryAtom, showInventoryAtom } from '@/atoms/Inventory'
import BasicItemFrame from '@/components/Inventory/BasicItemComponent'
import ItemComponent from '@/components/Inventory/ItemComponent'
import Close from '@/icons/Close'
import {
  AllowedInventoryItemTypes,
  ComplementaryItems,
  ItemRarities,
} from '@/utils/index'
import { useAtom, useSetAtom } from 'jotai'
import { useEffect, useState } from 'react'
import { toast } from 'react-hot-toast'
import { useTranslation } from '../../../public/locales/client'
import ImageMatchingModal from './ImageMatchingModal'
import ResetInventory from './ResetInventory'

export default function Inventory() {
  const [inventory, setInventory] = useAtom(InventoryAtom)
  const setShowInventory = useSetAtom(showInventoryAtom)
  const [showImageMatching, setShowImageMatching] = useState(false)
  const { t } = useTranslation()

  useEffect(() => {
    const inventoryIsUpdated = AllowedInventoryItemTypes.every((item) =>
      Object.keys(inventory).includes(item)
    )

    if (!inventoryIsUpdated) {
      toast.loading('Updating inventory...', { id: 'updateInventory' })
      setInventory((prev) => ({
        ...Object.assign(
          {},
          ...AllowedInventoryItemTypes.map((item) => ({
            [item]: ComplementaryItems.includes(item)
              ? 0
              : Object.assign(
                  {},
                  ...ItemRarities.map((r) => ({
                    [r]: {
                      nonTraddable: 0,
                      traddable: 0,
                    },
                  }))
                ),
          }))
        ),
        ...prev,
      }))
      toast.dismiss('updateInventory')
    }
  }, [])

  return (
    <div className="flex w-full max-w-[100rem] flex-col gap-8 self-center font-main">
      <header className="flex items-center justify-end gap-4">
        <h2 className="text-3xl text-primary-200 mr-auto">{t('Inventory')}</h2>

        <ResetInventory />

        <ImageMatchingModal
          show={showImageMatching}
          setShow={setShowImageMatching}
        />

        <button
          onClick={() => {
            setShowInventory((prev) => !prev)
          }}
          className="h-12 w-12 rounded-md p-3 transition-colors hover:bg-gray-100/10"
          aria-label="Close modal"
        >
          <Close className="fill-white" />
        </button>
      </header>

      <section className="grid grid-cols-[repeat(auto-fit,_minmax(180px,1fr))] gap-8">
        {Object.entries(inventory).map(
          ([name, rarities]) =>
            typeof rarities !== 'number' && (
              <ItemComponent key={name} item={name as ItemWithRarity} />
            )
        )}
      </section>

      <section className="mt-8 grid grid-cols-[repeat(auto-fill,_minmax(136px,1fr))] gap-8">
        {Object.entries(inventory)
          .filter(([, value]) => typeof value === 'number')
          ?.map(([name]) => (
            <BasicItemFrame
              key={name}
              item={name as NonRarityItems}
              rarity={'Default'}
            />
          ))}
      </section>
    </div>
  )
}
