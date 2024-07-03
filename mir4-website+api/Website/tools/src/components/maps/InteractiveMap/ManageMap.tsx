'use client'
import {
  MapsAtom,
  currentMapPointsAtom,
  rarityVisibilityAtom,
} from '@/atoms/Maps'
import Button from '@/components/maps/Button'
import { mapNodeTypes, nodeTypeToIcon } from '@/components/maps/InteractiveMap'
import RarityToggle from '@/components/maps/RarityToggle'
import Modal from '@/components/shared/Modal'

import Reset from '@/icons/Reset'
import { cn } from '@/utils/classNames'
import { toCamelCase } from '@/utils/index'
import { useAtom, useAtomValue } from 'jotai'

export default function ManageMap({ mapFloor }: { mapFloor?: string }) {
  const mapsStack = useAtomValue(MapsAtom)
  const [rarityVisibility, setRarityVisibility] = useAtom(rarityVisibilityAtom)
  const [currentMapPoints, setCurrentMapPoints] = useAtom(currentMapPointsAtom)

  const lastMap = mapsStack.at(-1) as mapTypes

  return (
    <section className="flex flex-col gap-4">
      <div className="flex h-full max-w-md flex-col gap-4 rounded-md border border-primary-500 bg-primary-600 p-4 pb-6 text-sm font-light text-white">
        <header className="flex gap-4">
          <Modal.Wrapper>
            <Modal.Trigger className="w-full rounded bg-primary-450 p-2 font-bold drop-shadow-md">
              Show controls
            </Modal.Trigger>
            <Modal.Content className="flex gap-4 rounded-md border border-primary-500 bg-primary-600 text-white">
              <ul className="flex flex-col gap-4 [&>li>kbd]:rounded [&>li>kbd]:border [&>li>kbd]:border-primary-500 [&>li>kbd]:bg-primary-800 [&>li>kbd]:px-2 [&>li>kbd]:py-1 [&>li>kbd]:drop-shadow-md">
                <li>
                  <kbd>hold left-click</kbd> - Move the map
                </li>
                <li>
                  <kbd>double left-click</kbd> - Zoom in on focused area
                </li>
                <li>
                  <kbd>right-click</kbd> - Create or Delete a node
                </li>
                <li>
                  <kbd>wheel-up</kbd> - Zoom In
                </li>
                <li>
                  <kbd>wheel-down</kbd> - Zoon Out
                </li>
              </ul>
            </Modal.Content>
          </Modal.Wrapper>

          <Button
            aria-label="Reset map nodes"
            onClick={() => setCurrentMapPoints({})}
          >
            <Reset />
          </Button>
        </header>

        <h2 className="text-xl">Visibility Settings</h2>

        <ul className="flex flex-col gap-4">
          {mapNodeTypes.map((nodeType) => {
            const hasSomeRarity = Object.values(
              rarityVisibility[nodeType]
            ).some((val) => val)
            const NodeIcon = nodeTypeToIcon[nodeType]

            return (
              <li key={nodeType} className="flex flex-row items-center gap-4">
                <Button
                  className={cn('mr-2 bg-white/5', {
                    'bg-white/10': hasSomeRarity,
                  })}
                  onClick={() =>
                    setRarityVisibility((prev) => ({
                      ...prev,
                      [nodeType]: hasSomeRarity
                        ? {
                            Legendary: false,
                            Epic: false,
                            Rare: false,
                            Uncommon: false,
                            Common: false,
                          }
                        : {
                            Legendary: true,
                            Epic: true,
                            Rare: true,
                            Uncommon: true,
                            Common: true,
                          },
                    }))
                  }
                >
                  <NodeIcon />
                </Button>
                <RarityToggle
                  isActive={rarityVisibility[nodeType]}
                  action={(rarity) =>
                    setRarityVisibility((prev) => ({
                      ...prev,
                      [nodeType]: {
                        ...prev[nodeType],
                        [rarity]: !prev[nodeType][rarity],
                      },
                    }))
                  }
                />
              </li>
            )
          })}
        </ul>

        <footer className="mt-auto flex flex-col gap-4">
          {/* <button
              className={
                'flex w-full cursor-pointer items-center justify-center rounded border-2 border-primary-400 bg-primary-500/50 p-3 font-medium leading-none transition-colors hover:bg-primary-500 focus:border-white focus:outline-none'
              }
            >
              Import map
            </button> */}

          <a
            href={`data:text/json;charset=utf-8,${encodeURIComponent(
              JSON.stringify(currentMapPoints, null, 2)
            )}`}
            download={`${toCamelCase(lastMap)}${
              mapFloor ? `-${mapFloor}` : ''
            }.json`}
            className="flex justify-center rounded bg-[#368D6E] p-3 text-xs font-extrabold text-white"
          >
            Export map as JSON
          </a>
        </footer>
      </div>

      <Modal.Wrapper>
        <Modal.Trigger
          asChild={false}
          className="w-full rounded bg-primary-450 p-2 py-4 font-bold text-white drop-shadow-md transition-colors hover:bg-primary-500"
        >
          Contributors
        </Modal.Trigger>
        <Modal.Content className="flex gap-8 pb-6 rounded-md border border-primary-500 bg-primary-600 text-white">
          <header className="flex w-full items-center justify-between">
            <Modal.Title>Thanks for helping us!</Modal.Title>
            <Modal.Close />
          </header>

          <ul className="list-disc pl-6">
            {mapContributors.map((contributor) => (
              <li key={contributor}>{contributor}</li>
            ))}
          </ul>
        </Modal.Content>
      </Modal.Wrapper>
    </section>
  )
}

const mapContributors = ['jonasodh', 'whoisbrunobarreto']
