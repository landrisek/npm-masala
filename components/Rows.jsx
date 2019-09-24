import React from 'react'

export class Rows {
    Row(key, state) {
        let row = [];
        for (let column in state) {
            row.push(<td key={'grid-col-' + column}>{state[column]}</td>)
        }
        return <tr key={'row-' + key}>{row}</tr>
    }

    Rows(state) {
        let rows = [];
        for (let key in state) {
            rows.push(this.Row(key, state[key]))
        }
        return rows
    }
}
