/* eslint-disable @typescript-eslint/no-use-before-define */
/* eslint-disable @typescript-eslint/method-signature-style */
/// <reference types="cypress" />
Cypress.Commands.add('checkItemRecipe', () => {
  cy.get('#totalCostWithRarity')
    .children()
    .children()
    .each((itemFrame) => {
      const formattedValue = Number(itemFrame.text().replace(/\D/g, ''))

      cy.wrap(formattedValue).should('be.gte', 0)
    })

  cy.get('#totalCostWithoutRarity')
    .children()
    .each((itemFrame) => {
      const formattedValue = Number(itemFrame.text().replace(/\D/g, ''))

      cy.wrap(formattedValue).should('be.gte', 0)
    })
})

// eslint-disable-next-line @typescript-eslint/no-namespace
declare namespace Cypress {
  interface Chainable<> {
    checkItemRecipe(): Chainable<any>
  }
}
