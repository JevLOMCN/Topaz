describe('Item vales are properly displayed', () => {
  it('Legendary recipes', () => {
    cy.visit('http://localhost:3000/')

    cy.get('#itemSelectorFrame').click()

    const category = cy.contains('Primary').parent().parent().children().eq(1)

    category.children().each((categoryType) => {
      const rarity = cy
        .wrap(categoryType)
        .parent()
        .parent()
        .children()
        .eq(0)
        .children()

      cy.wait(150)
        .then(() => categoryType.trigger('click'))
        .then(() =>
          rarity.each((rarityType) => {
            const tier = cy
              .wrap(rarityType)
              .parent()
              .parent()
              .children()
              .eq(2)
              .children()

            cy.wait(150)
              .then(() => rarityType.trigger('click'))
              .then(() =>
                tier.each((tier) => {
                  cy.wait(150)
                    .then(() => tier.trigger('click'))
                    .then(() => cy.checkItemRecipe())
                })
              )
          })
        )
    })
  })
})
