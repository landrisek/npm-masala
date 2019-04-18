import React from 'react'
import classNames from 'classnames'
import Dropzone from 'react-dropzone'

export class Upload extends React.Component {
    constructor(props) {
        super(props)
    }
    render() {
        return (
          <Dropzone onDrop={this.props.onDrop}>
            {({getRootProps, getInputProps}) => {
              return (
                <div {...getRootProps()} 
                    className={'dropzone'} 
                    style={{borderWidth:'2px',borderColor:'rgb(102, 102, 102)',borderStyle:'dashed',borderRadius:'5px',height:'100px',widht:'100%'}}>
                  <input {...getInputProps()} />
                  <p>{this.props.data.label}</p>
                </div>
              )
            }}
          </Dropzone>
        );
  }
}