import { InventoryAtom } from '@/atoms/Inventory'
import Robot from '@/icons/Robot'
import { cn } from '@/utils/classNames'
import {
  AllowedInventoryItemTypes,
  ItemRarities,
  deepMerge,
} from '@/utils/index'
import { useAtom } from 'jotai'
import { useState, type SetStateAction } from 'react'
import { useTranslation } from '../../../public/locales/client'
import FileInput from '../shared/FileInput'
import Modal from '../shared/Modal'

export default function ImageMatchingModal({
  show,
  setShow,
}: {
  show: boolean
  setShow: React.Dispatch<SetStateAction<boolean>>
}) {
  const [inventory, setInventory] = useAtom(InventoryAtom)
  const [input, setInput] = useState('')
  const [error, setError] = useState({ hasError: false, message: '' })
  const { t } = useTranslation()

  const validateJSON = () => {
    try {
      const parsedValue = JSON.parse(input)
      const items = Object.keys(parsedValue)
      const values = Object.values(parsedValue)

      const unknownRarity = values
        .flatMap((obj) => Object.keys(obj as object))
        .some((rarity) => !ItemRarities.includes(rarity as RarityTypes))

      if (unknownRarity) {
        return setError({
          hasError: true,
          message: 'Invalid item rarity detect.',
        })
      }

      // Check for invalid values by converting them
      const hasDigitError = values
        .flatMap((obj) =>
          Object.values(obj as any).flatMap((rarity) =>
            Object.values(rarity as any).map((value) => value as number)
          )
        )
        .some((value) => typeof value !== 'number' || value < 0)

      if (hasDigitError) {
        return setError({
          hasError: true,
          message: 'Invalid item amount detect.',
        })
      }

      if (!items.every((item) => AllowedInventoryItemTypes.includes(item))) {
        return setError({
          hasError: true,
          message: 'Non-allowed item detected on the JSON object.',
        })
      }

      setError({ hasError: false, message: '' })

      const testing = deepMerge(inventory, parsedValue)

      setInput('')
      setInventory(testing)
      setShow(false)
    } catch (error: any) {
      setError({
        hasError: true,
        message:
          'Something went wrong, ensure you copied the JSON object correctly.',
      })
    }
  }

  return (
    <Modal.Wrapper open={show} onOpenChange={setShow}>
      <Modal.Trigger
        className="h-12 w-14 rounded-md p-3 transition-colors hover:bg-gray-100/10"
        aria-label="Inventory image matcher"
      >
        <Robot className="fill-white" />
      </Modal.Trigger>
      <Modal.Content className="max-w-md gap-4 border-primary-500 bg-primary-600 pb-6 text-sm font-light text-white">
        <header className="flex w-full items-center justify-between">
          <Modal.Title className="text-2xl font-medium">
            {t('Inventory image matching')}
          </Modal.Title>
          <Modal.Close />
        </header>

        <p>
          {t(`This is a tool developed for players to get their inventory into
              the website faster, join our discord, and read the instructions to
              use it.`)}
        </p>

        <p>
          {t(
            'After getting a JSON return, make sure the values are correct, then add the items to your inventory.'
          )}
        </p>

        <a
          href="https://discord.gg/BRJxNdbJgw"
          rel="noreferrer"
          target="_blank"
          className="font-bold underline"
        >
          Mir4Tools Discord link
        </a>

        <FileInput
          label="Choose a JSON file"
          accept="application/JSON"
          onJSONChange={(json) => setInput(json)}
        />

        <p className="relative flex w-full items-center justify-center border-b border-primary-400 py-2 font-medium">
          <span className="absolute bg-primary-600 px-4">
            Or paste your JSON here
          </span>
        </p>

        <textarea
          spellCheck={false}
          rows={40}
          value={input}
          onChange={(e) => setInput(e.currentTarget.value)}
          className={cn(
            'custom-scroll resize-none rounded-lg border-2 border-primary-400 bg-primary-500/50 p-4 font-medium transition-colors focus:border-white focus:outline-none',
            {
              'border-red-400 bg-red-400/10 focus:border-red-400':
                error.hasError,
            }
          )}
        />

        {error.hasError && (
          <div className="rounded-md border-2 border-red-400 bg-red-400/10 p-4 text-sm font-bold">
            {error.message}
          </div>
        )}

        <button
          onClick={validateJSON}
          className="flex rounded bg-[#368D6E] p-3 text-xs font-extrabold uppercase text-white"
        >
          {t('Add to inventory')}
        </button>
      </Modal.Content>
    </Modal.Wrapper>
  )
}
