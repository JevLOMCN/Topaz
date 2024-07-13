import CostFragment from '@/components/crafting/CostFragment'
import {
  ComplementaryItems,
  extractItemRarity,
  formatItemName,
} from '@/utils/index'
import { useTranslation } from '../../../public/locales/client'
import AdditionalCostFragment from './TableCostFragment/AdditionalCostFragment'

export default function TotalCost({
  itemFullRecipe,
  formattedRecipe,
}: {
  itemFullRecipe: Record<string, number>
  formattedRecipe: Array<Array<[string, number]>>
}) {
  const { t } = useTranslation()

  return (
    <section id="totalCostPanel" className="flex w-full flex-col gap-8">
      <h2 className="text-2xl font-bold text-white md:text-3xl">
        {t('Total')}
      </h2>

      <div className="flex w-full flex-col gap-5 lg:flex-row">
        <section
          id="totalCostWithRarity"
          className="flex w-full flex-col gap-6"
        >
          {formattedRecipe.map((rarityColumn, index) => (
            <ul className="flex items-center gap-6" key={index}>
              {rarityColumn.map(([name, amount]) => {
                if (ComplementaryItems.includes(name)) {
                  return <></>
                }

                const formattedName = formatItemName(name)
                const itemRarity = extractItemRarity(name)

                return (
                  <CostFragment
                    key={name}
                    name={formattedName}
                    cost={amount}
                    rarity={itemRarity}
                    disabledMillify
                  />
                )
              })}
            </ul>
          ))}
        </section>

        <ul id="totalCostWithoutRarity" className="flex gap-5">
          {Number.isInteger(itemFullRecipe.Darksteel) && (
            <AdditionalCostFragment
              name="darksteel"
              cost={itemFullRecipe.Darksteel}
              rarity="Default"
            />
          )}

          {Number.isInteger(itemFullRecipe.Copper) && (
            <AdditionalCostFragment
              name="copper"
              cost={itemFullRecipe.Copper}
              rarity="Default"
            />
          )}

          {Number.isInteger(itemFullRecipe['Glittering Powder']) && (
            <AdditionalCostFragment
              name="glittering_powder"
              cost={itemFullRecipe['Glittering Powder']}
              rarity="Uncommon"
            />
          )}
        </ul>
      </div>
    </section>
  )
}
